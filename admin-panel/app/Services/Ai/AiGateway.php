<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Models\Academics\ContentSource;
use App\Models\Academics\ContentVersion;
use App\Models\Ai\AiFlag;
use App\Models\Ai\AiUsage;
use App\Models\Platform\School;
use App\Models\User;
use App\Policies\Academics\ContentPolicy;
use App\Services\Academics\Content\ContentProcessingFailure;
use App\Services\Academics\Content\ContentSettings;
use App\Services\Academics\Content\ConverterClient;
use App\Services\Ai\Inference\Capability;
use App\Services\Ai\Inference\InferenceGateway;
use App\Services\Ai\Inference\InferenceRequest;
use App\Services\Ai\Inference\ProviderResponse;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/** All model calls share this boundary; external inference remains disabled by default. */
class AiGateway
{
    public function complete(User $actor, InferenceRequest $request): ProviderResponse
    {
        return app(InferenceGateway::class)->run($actor, Capability::Complete, $request);
    }

    public function vision(User $actor, InferenceRequest $request): ProviderResponse
    {
        return app(InferenceGateway::class)->run($actor, Capability::Vision, $request);
    }

    public function embed(User $actor, InferenceRequest $request): ProviderResponse
    {
        return app(InferenceGateway::class)->run($actor, Capability::Embed, $request);
    }

    private const FEATURE = 'content_transcription';

    private function reserve(User $actor, ContentVersion $version, array $source): AiUsage
    {
        return DB::transaction(function () use ($actor, $version, $source) {
            School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
            app(ContentPolicy::class)->version($actor, (int) $version->id, true);
            $fresh = ContentVersion::with('item')->findOrFail($version->id);
            if ($fresh->state !== 'converting' || ! Str::isUuid((string) $fresh->attempt_id) || $fresh->lease_until === null || ! $fresh->lease_until->isFuture()
                || (int) $fresh->item->draft_version_id !== (int) $fresh->id || $fresh->attempt_id !== $version->attempt_id) {
                throw new ContentProcessingFailure('transcription_attempt_stale', false);
            }
            $settings = app(ContentSettings::class)->data();
            if (! $settings['transcription_enabled']) {
                throw new ContentProcessingFailure('transcription_disabled', false);
            }
            if (config('ai.transcribe.provider') !== 'local-whisper-base') {
                throw new ContentProcessingFailure('transcription_provider_unavailable', false);
            }
            $hash = hash('sha256', json_encode($source, JSON_THROW_ON_ERROR));
            $prior = AiUsage::where('feature', self::FEATURE)->where('operation_id', $version->attempt_id)->first();
            if ($prior !== null) {
                throw new ContentProcessingFailure('transcription_attempt_already_reserved', false);
            }
            $day = now(app(CurrentSchool::class)->school()->timezone)->toDateString();
            $used = (int) AiUsage::where('feature', self::FEATURE)->where('usage_date', $day)->selectRaw('COALESCE(SUM(reserved_units + used_units),0) as units')->value('units');
            $reserve = min((int) config('ai.transcribe.maximum_seconds'), $settings['daily_transcription_seconds'] - $used);
            if ($reserve < 1) {
                throw new ContentProcessingFailure('transcription_daily_cap', false);
            }

            return AiUsage::create(['user_id' => $actor->id, 'learner_id' => null, 'feature' => self::FEATURE, 'provider' => (string) config('ai.transcribe.provider'), 'operation_id' => $version->attempt_id,
                'request_hash' => $hash, 'usage_date' => $day, 'unit' => 'seconds', 'state' => 'reserved', 'reserved_units' => $reserve, 'used_units' => 0, 'cost_cents' => 0]);
        });
    }

    public function transcribe(User $actor, ContentVersion $version, array $source): array
    {
        app(TranscriptionModeration::class)->input(ContentSource::findOrFail($version->content_source_id));
        $usage = $this->reserve($actor, $version, $source);
        try {
            $source['maximum_seconds'] = $usage->reserved_units;
            $result = app(ConverterClient::class)->run($source, true);
            $flags = app(TranscriptionModeration::class)->flags($result['markdown']);
            DB::transaction(function () use ($usage, $result, $flags) {
                School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
                $usage->update(['state' => $flags === [] ? 'completed' : 'quarantined', 'reserved_units' => 0, 'used_units' => $result['duration_seconds'], 'output_sha256' => hash('sha256', $result['markdown']), 'completed_at' => now()]);
                foreach ($flags as $flag) {
                    AiFlag::create(['ai_usage_id' => $usage->id, 'code' => $flag]);
                }
                Audit::record('ai.transcription_metered', $usage, [], ['seconds' => $result['duration_seconds'], 'provider' => $usage->provider, 'quarantined' => $flags !== [], 'cost_cents' => 0]);
            });
            if ($flags !== []) {
                throw new ContentProcessingFailure('transcription_safeguarding_review', false);
            }

            return $result;
        } catch (Throwable $e) {
            // Unknown upstream outcome remains charged at the reserved duration;
            // retrying an interrupted request cannot make its compute unmetered.
            DB::transaction(function () use ($usage, $e) {
                School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
                $fresh = AiUsage::where('state', 'reserved')->find($usage->id);
                if ($fresh !== null) {
                    $fresh->update(['state' => 'failed', 'used_units' => $e instanceof ContentProcessingFailure && ! $e->chargeReservation ? 0 : $fresh->reserved_units, 'reserved_units' => 0, 'failure_code' => $e instanceof ContentProcessingFailure ? $e->failureCode : 'transcription_failed', 'completed_at' => now()]);
                }
            });
            throw $e;
        }
    }
}
