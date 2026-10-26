<?php

declare(strict_types=1);

namespace App\Services\Ai\Quarantine;

use App\Models\Ai\AiFlag;
use App\Models\Ai\AiFlagReview;
use App\Models\Ai\AiQuarantineSnapshot;
use App\Models\Ai\AiUsage;
use App\Models\Platform\School;
use App\Models\User;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class AiFlagReviews
{
    public function owned(User $actor): Builder
    {
        $fresh = app(AiReviewAuthority::class)->actor($actor);
        $schoolId = app(CurrentSchool::class)->id();
        $actorId = (int) $fresh->id;

        return AiFlag::where('school_id', $schoolId)->withGlobalScope('fresh_ai_reviewer', function (Builder $query) use ($schoolId, $actorId): void {
            $school = app(CurrentSchool::class);
            if (! $school->isSet() || $school->isAcrossSchools() || $school->isImpersonating() || $school->id() !== $schoolId) {
                $query->whereRaw('1 = 0');

                return;
            }
            try {
                app(AiReviewAuthority::class)->actor(User::findOrFail($actorId));
                $cutoff = now()->subDays(app(AiQuarantine::class)->retentionDays());
                $query->whereExists(fn ($usage) => $usage->selectRaw('1')->from('ai_usage')->whereColumn('ai_usage.id', 'ai_flags.ai_usage_id')->whereColumn('ai_usage.school_id', 'ai_flags.school_id'))
                    ->where(function ($visible) use ($cutoff): void {
                        $visible->where(fn ($old) => $old->whereNull('quarantine_snapshot_id')->where('ai_flags.created_at', '>', $cutoff))
                            ->orWhereExists(fn ($snapshots) => $snapshots->selectRaw('1')->from('ai_quarantine_snapshots')
                                ->whereColumn('ai_quarantine_snapshots.id', 'ai_flags.quarantine_snapshot_id')->whereColumn('ai_quarantine_snapshots.school_id', 'ai_flags.school_id')
                                ->whereColumn('ai_quarantine_snapshots.ai_usage_id', 'ai_flags.ai_usage_id')
                                ->where('ai_quarantine_snapshots.expires_at', '>', now())->where('ai_quarantine_snapshots.created_at', '>', $cutoff));
                    });
            } catch (HttpExceptionInterface|ModelNotFoundException) {
                $query->whereRaw('1 = 0');
            }
        });
    }

    public function listing(User $actor, int $page = 1): array
    {
        $query = $this->owned($actor);
        $total = (clone $query)->count();
        $items = $query->latest('id')->skip((max(1, $page) - 1) * 20)->limit(20)->get()
            ->map(fn ($flag) => ['id' => (int) $flag->id, 'code' => $flag->code, 'status' => $flag->review_status, 'created_at' => $flag->created_at?->toIso8601String()])->all();
        app(AiReviewAuthority::class)->actor($actor);

        return ['items' => $items, 'page' => max(1, $page), 'total' => $total, 'last_page' => max(1, (int) ceil($total / 20))];
    }

    public function show(User $actor, int $id): array
    {
        $flag = $this->owned($actor)->findOrFail($id);
        app(AiReviewAuthority::class)->actor($actor);
        $snapshot = $flag->quarantine_snapshot_id === null ? null : AiQuarantineSnapshot::where('ai_usage_id', $flag->ai_usage_id)->findOrFail($flag->quarantine_snapshot_id);
        $usage = AiUsage::findOrFail($flag->ai_usage_id);
        $content = $snapshot?->private_content;
        $reviews = AiFlagReview::where('ai_flag_id', $flag->id)->orderBy('revision')->get()->map(fn ($review) => [
            'decision' => $review->decision, 'reason' => $review->private_reason, 'revision' => (int) $review->revision, 'at' => $review->created_at->toIso8601String(),
        ])->all();
        $this->owned($actor)->whereKey($id)->firstOrFail();

        return ['id' => (int) $flag->id, 'code' => $flag->code, 'status' => $flag->review_status, 'revision' => (int) $flag->review_revision,
            'feature' => $usage->feature, 'prompt' => $usage->prompt_key, 'prompt_version' => $usage->prompt_version,
            'stage' => $snapshot?->stage, 'content' => $content, 'expires_at' => ($snapshot === null ? $flag->created_at->addDays(app(AiQuarantine::class)->retentionDays()) : $snapshot->expires_at->min($snapshot->created_at->addDays(app(AiQuarantine::class)->retentionDays())))->toIso8601String(), 'reviews' => $reviews];
    }

    /** A feature must explicitly register its cropped-media reader. */
    public function media(User $actor, int $id, string $referenceId): array
    {
        $detail = $this->show($actor, $id);
        $reference = collect($detail['content']['media'] ?? [])->first(fn ($item) => (string) $item['id'] === $referenceId);
        abort_if($reference === null, 404);
        $flag = $this->owned($actor)->findOrFail($id);
        $usage = AiUsage::findOrFail($flag->ai_usage_id);
        $resolver = config('ai.quarantine.media_resolvers.'.$usage->feature);
        abort_unless(is_string($resolver) && is_a($resolver, QuarantineMediaResolver::class, true), 404);
        $schoolId = app(CurrentSchool::class)->id();
        try {
            $result = app($resolver)->read($actor, $usage, $reference);
        } finally {
            $school = app(CurrentSchool::class);
            if (! $school->isSet() || $school->isAcrossSchools() || $school->isImpersonating() || $school->id() !== $schoolId) {
                $school->set($schoolId);
                abort(403);
            }
        }
        $this->owned($actor)->whereKey($id)->firstOrFail();
        abort_unless(is_string($result['bytes'] ?? null) && strlen($result['bytes']) <= 8388608
            && ($result['mime'] ?? null) === $reference['mime'] && hash_equals($reference['sha256'], hash('sha256', $result['bytes'])), 404);
        $image = @getimagesizefromstring($result['bytes']);
        abort_unless(is_array($image) && $image[0] > 0 && $image[1] > 0 && max($image[0], $image[1]) <= 8192 && $image['mime'] === $reference['mime'], 404);

        return $result;
    }

    public function review(User $actor, int $id, array $data): array
    {
        app(AiReviewAuthority::class)->actor($actor);
        $this->owned($actor)->whereKey($id)->firstOrFail();
        $data = Validator::make($data, ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:1'],
            'decision' => ['required', 'in:reviewed,escalated'], 'reason' => ['required', 'string', 'max:2000']])->validate();
        $schoolId = app(CurrentSchool::class)->id();
        $hash = hash_hmac('sha256', json_encode([$id, $data], JSON_THROW_ON_ERROR), (string) config('app.key'));

        return DB::transaction(function () use ($actor, $id, $data, $schoolId, $hash): array {
            School::whereKey($schoolId)->lockForUpdate()->firstOrFail();
            app(AiReviewAuthority::class)->actor($actor);
            $flag = $this->owned($actor)->lockForUpdate()->findOrFail($id);
            $old = AiFlagReview::where('reviewed_by', $actor->id)->where('operation_id', $data['operation_id'])->first();
            if ($old !== null) {
                abort_unless(hash_equals($old->intent_hash, $hash), 409, 'The review operation has a different intent.');

                return ['id' => $id, 'revision' => (int) $old->revision];
            }
            abort_unless((int) $flag->review_revision === (int) $data['revision'], 409, 'This flag review changed. Refresh before continuing.');
            $revision = (int) $flag->review_revision + 1;
            AiFlagReview::create(['ai_flag_id' => $id, 'reviewed_by' => $actor->id, 'operation_id' => $data['operation_id'], 'intent_hash' => $hash,
                'decision' => $data['decision'], 'private_reason' => $data['reason'], 'revision' => $revision]);
            $flag->update(['review_status' => $data['decision'], 'review_revision' => $revision]);
            Audit::record('ai.flag_reviewed', $flag, [], ['decision' => $data['decision'], 'revision' => $revision]);
            $this->owned($actor)->whereKey($id)->firstOrFail();

            return ['id' => $id, 'revision' => $revision];
        });
    }
}
