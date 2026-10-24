<?php

declare(strict_types=1);

namespace App\Services\Academics\Content;

use App\Models\Academics\ContentItem;
use App\Models\Academics\ContentSource;
use App\Models\Academics\ContentVersion;
use App\Models\Ai\AiUsage;
use App\Models\Platform\School;
use App\Models\User;
use App\Policies\Academics\ContentPolicy;
use App\Services\Ai\AiGateway;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Files\UploadScanner;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class ContentPipeline
{
    private function current(int $id, string $generation, ?string $attempt = null): ?ContentVersion
    {
        $q = ContentVersion::with('item', 'source')->where('generation_id', $generation);
        if ($attempt !== null) {
            $q->where('attempt_id', $attempt);
        }
        $v = $q->find($id);

        return $v !== null && (int) $v->item?->draft_version_id === (int) $v->id && $v->published_at === null ? $v : null;
    }

    private function actor(ContentVersion $version): User
    {
        $actor = User::where('status', 1)->findOrFail($version->created_by);
        app(ContentPolicy::class)->version($actor, (int) $version->id, true);

        return $actor;
    }

    private function claim(int $id, string $generation): ?ContentVersion
    {
        return DB::transaction(function () use ($id, $generation) {
            School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
            $v = $this->current($id, $generation);
            if ($v === null || ! in_array($v->state, ['queued', 'scanning', 'converting'], true) || ($v->state !== 'queued' && $v->lease_until?->isFuture())) {
                return null;
            }
            $this->actor($v);
            if ($v->attempt_id !== null) {
                AiUsage::where('feature', 'content_transcription')->where('operation_id', $v->attempt_id)->where('state', 'reserved')
                    ->update(['state' => 'failed', 'used_units' => DB::raw('reserved_units'), 'reserved_units' => 0, 'failure_code' => 'content_worker_interrupted', 'completed_at' => now()]);
            }
            $v->forceFill(['state' => 'scanning', 'attempt_id' => (string) Str::uuid(), 'lease_until' => now()->addSeconds((int) config('academic_content.lease_seconds')), 'failure_code' => null, 'revision' => $v->revision + 1])->save();

            return $v;
        });
    }

    public function sourceData(ContentVersion $version): array
    {
        $source = $version->source;
        $relative = URL::temporarySignedRoute('content-converter.source', now()->addMinutes(5), ['school' => app(CurrentSchool::class)->id(), 'version' => $version->id, 'generation' => $version->generation_id], false);
        $origin = rtrim((string) config('academic_content.source_origin'), '/');
        $p = parse_url($origin);
        if (! is_array($p) || ! isset($p['host']) || isset($p['user']) || isset($p['pass']) || isset($p['query']) || isset($p['fragment']) || ! empty($p['path']) || ! in_array($p['scheme'] ?? '', config('academic_content.allow_http') ? ['https', 'http'] : ['https'], true)) {
            throw new ContentProcessingFailure('content_source_origin_unavailable');
        }

        return ['school_id' => app(CurrentSchool::class)->id(), 'version_id' => (int) $version->id, 'generation_id' => $version->generation_id, 'source_url' => $origin.$relative,
            'source_sha256' => $source->sha256, 'source_bytes' => $source->bytes, 'extension' => $source->extension];
    }

    /** Called only after a signed relative capability chooses its school. */
    public function source(int $id, string $generation): ContentSource
    {
        $v = $this->current($id, $generation);
        abort_unless($v !== null && $v->state === 'converting' && $v->lease_until?->isFuture() && $v->source?->scan_state === 'clean', 404);
        $this->actor($v);
        abort_unless((int) $v->source->content_item_id === (int) $v->content_item_id, 404);

        return $v->source;
    }

    public function run(int $id, string $generation): void
    {
        $version = null;
        try {
            $version = $this->claim($id, $generation);
            if ($version === null) {
                return;
            }
            $actor = $this->actor($version);
            $bytes = app(ContentFiles::class)->read($version->source);
            $scan = app(UploadScanner::class)->scan($bytes);
            unset($bytes);
            $ready = DB::transaction(function () use ($version, $scan) {
                School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
                $v = $this->current((int) $version->id, $version->generation_id, $version->attempt_id);
                if ($v === null || $v->state !== 'scanning' || ! $v->lease_until?->isFuture()) {
                    return false;
                }
                $this->actor($v);
                $v->source->update(['scan_state' => $scan['clean'] ? 'clean' : 'infected', 'scan_engine' => $scan['engine'], 'scan_signature' => $scan['signature'], 'scanned_at' => now()]);
                Audit::record('academics.content_source_scanned', $v->source, [], ['clean' => $scan['clean'], 'engine' => $scan['engine'], 'sha256' => $v->source->sha256]);
                if (! $scan['clean']) {
                    $v->update(['state' => 'quarantined', 'failure_code' => 'source_infected', 'lease_until' => null, 'revision' => $v->revision + 1]);
                    ContentItem::where('id', $v->content_item_id)->whereHas('published', fn ($q) => $q->where('content_source_id', $v->content_source_id))->update(['active' => false, 'revision' => DB::raw('revision + 1')]);

                    return false;
                }
                $v->update(['state' => 'converting', 'lease_until' => now()->addSeconds((int) config('academic_content.lease_seconds')), 'revision' => $v->revision + 1]);

                return true;
            });
            if (! $ready) {
                return;
            }
            $version = ContentVersion::with('source', 'item')->findOrFail($id);
            if ($version->markdown !== null) {
                $result = ['markdown' => $version->markdown, 'duration_seconds' => $version->duration_seconds ?? 0, 'language' => $version->language];
            } else {
                $payload = $this->sourceData($version);
                $result = in_array($version->source->extension, ContentFiles::MEDIA, true)
                    ? app(AiGateway::class)->transcribe($actor, $version, $payload) : app(ConverterClient::class)->run($payload);
            }
            $clean = app(ContentMarkdown::class)->clean($result['markdown']);
            DB::transaction(function () use ($version, $result, $clean) {
                School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
                $v = $this->current((int) $version->id, $version->generation_id, $version->attempt_id);
                if ($v === null || $v->state !== 'converting' || ! $v->lease_until?->isFuture()) {
                    return;
                }
                $this->actor($v);
                if ($v->source?->scan_state !== 'clean') {
                    throw new ContentProcessingFailure('source_not_clean', false);
                }
                $v->update(['state' => 'draft', 'markdown' => $clean, 'duration_seconds' => $result['duration_seconds'], 'language' => $result['language'], 'failure_code' => null, 'lease_until' => null, 'revision' => $v->revision + 1]);
                Audit::record('academics.content_converted', $v, [], ['markdown_sha256' => hash('sha256', $clean), 'source_id' => (int) $v->content_source_id, 'review_required' => true]);
            });
        } catch (Throwable $e) {
            $scanner = $e instanceof \RuntimeException && in_array($e->getMessage(), ['scanner_unavailable', 'scanner_definitions_outdated', 'scanner_source_limit'], true) ? $e->getMessage() : null;
            $failure = $e instanceof ContentProcessingFailure ? $e : new ContentProcessingFailure($scanner ?? ($e instanceof HttpExceptionInterface || $e instanceof ModelNotFoundException ? 'source_or_authority_unavailable' : 'content_processing_failed'), ! ($e instanceof HttpExceptionInterface || $e instanceof ModelNotFoundException));
            $this->fail($id, $generation, $version?->attempt_id, $failure->failureCode, $failure->retryable);
            if ($failure->retryable) {
                throw $failure;
            }
        }
    }

    private function fail(int $id, string $generation, ?string $attempt, string $code, bool $retry): void
    {
        DB::transaction(function () use ($id, $generation, $attempt, $code, $retry) {
            School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
            $v = $this->current($id, $generation, $attempt);
            if ($v === null || ! in_array($v->state, ['queued', 'scanning', 'converting'], true)) {
                return;
            }
            $v->update(['state' => $code === 'transcription_safeguarding_review' ? 'quarantined' : ($retry ? 'queued' : 'failed'), 'failure_code' => $code, 'lease_until' => null, 'revision' => $v->revision + 1]);
            Audit::record('academics.content_conversion_failed', $v, [], ['failure_code' => $code, 'retryable' => $retry]);
        });
    }

    public function exhausted(int $id, string $generation): void
    {
        $v = $this->current($id, $generation);
        if ($v !== null && ($v->state === 'queued' || ($v->lease_until !== null && $v->lease_until->lte(now())))) {
            $this->fail($id, $generation, $v->attempt_id, $v->failure_code ?? 'content_worker_interrupted', false);
        }
    }
}
