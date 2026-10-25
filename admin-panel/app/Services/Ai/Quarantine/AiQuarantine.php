<?php

declare(strict_types=1);

namespace App\Services\Ai\Quarantine;

use App\Models\Ai\AiFlag;
use App\Models\Ai\AiQuarantineSnapshot;
use App\Models\Ai\AiUsage;
use App\Models\Platform\School;
use App\Services\Ai\Inference\AiUnavailable;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;

/** Server-only capture and retention. Review authority is a separate boundary. */
class AiQuarantine
{
    public function retentionDays(): int
    {
        return max(1, min(30, (int) config('ai.quarantine.retention_days', 30)));
    }

    private function context(int $schoolId): void
    {
        $school = app(CurrentSchool::class);
        if (! $school->isSet() || $school->isAcrossSchools() || $school->isImpersonating() || $school->id() !== $schoolId) {
            throw new AiUnavailable('inference_context_changed');
        }
    }

    public function capture(AiUsage $usage, string $stage, array $flags, string|array|null $content): void
    {
        if ($flags === []) {
            return;
        }
        $schoolId = app(CurrentSchool::class)->id();
        $this->context($schoolId);
        if (! in_array($stage, ['input', 'output'], true) || count($flags) > 64) {
            throw new AiUnavailable('moderation_invalid');
        }
        foreach ($flags as $flag) {
            if (! is_string($flag) || ! preg_match('/^[a-z][a-z0-9_]{0,63}$/D', $flag)) {
                throw new AiUnavailable('moderation_invalid');
            }
        }
        $payload = $this->payload($content);
        $encoded = json_encode($payload, JSON_THROW_ON_ERROR);
        if (strlen($encoded) > 524288 || (string) config('app.key') === '') {
            throw new AiUnavailable('moderation_invalid');
        }
        $hash = hash_hmac('sha256', $encoded, (string) config('app.key'));
        DB::transaction(function () use ($usage, $stage, $flags, $payload, $hash, $schoolId): void {
            School::whereKey($schoolId)->lockForUpdate()->firstOrFail();
            $this->context($schoolId);
            $fresh = AiUsage::lockForUpdate()->findOrFail($usage->id);
            if ((int) $fresh->school_id !== (int) $usage->getRawOriginal('school_id') ||
                $fresh->quarantine_purged_at !== null || ! in_array($fresh->state, ['reserved', 'quarantined'], true)) {
                throw new AiUnavailable('operation_not_replayable');
            }
            $snapshot = AiQuarantineSnapshot::where('ai_usage_id', $fresh->id)->where('stage', $stage)->first();
            if ($snapshot !== null && ! hash_equals($snapshot->content_hash, $hash)) {
                throw new AiUnavailable('operation_conflict');
            }
            $snapshot ??= AiQuarantineSnapshot::create(['ai_usage_id' => $fresh->id, 'stage' => $stage,
                'private_content' => $payload, 'content_hash' => $hash, 'expires_at' => now()->addDays($this->retentionDays())]);
            foreach (array_unique($flags) as $flag) {
                AiFlag::firstOrCreate(['ai_usage_id' => $fresh->id, 'quarantine_snapshot_id' => $snapshot->id, 'code' => $flag]);
            }
            $fresh->update(['state' => 'quarantined']);
            $this->context($schoolId);
        });
        $usage->refresh();
    }

    private function payload(string|array|null $content): array
    {
        if (is_string($content) || $content === null) {
            return ['text' => $content];
        }
        if (array_is_list($content)) {
            // An embedding is not reviewable prose; retain only its shape.
            if (count($content) > 4096 || collect($content)->contains(fn ($value) => ! is_numeric($value) || ! is_finite((float) $value))) {
                throw new AiUnavailable('moderation_invalid');
            }

            return ['embedding_dimensions' => count($content)];
        }
        if (array_diff(array_keys($content), ['text', 'media']) !== [] || ! is_string($content['text'] ?? null) ||
            ! is_array($content['media'] ?? []) || count($content['media'] ?? []) > 3) {
            throw new AiUnavailable('moderation_invalid');
        }
        foreach ($content['media'] ?? [] as $media) {
            if (! is_array($media) || count($media) !== 3 || array_diff(array_keys($media), ['id', 'sha256', 'mime']) !== [] ||
                ! is_string($media['sha256'] ?? null) || ! preg_match('/^[a-f0-9]{64}$/D', $media['sha256']) ||
                ! in_array($media['mime'] ?? null, ['image/jpeg', 'image/png', 'image/webp'], true) ||
                ! ((is_int($media['id'] ?? null) && $media['id'] > 0) ||
                    (is_string($media['id'] ?? null) && preg_match('/^[A-Za-z0-9_-]{1,100}$/D', $media['id'])))) {
                throw new AiUnavailable('moderation_invalid');
            }
        }

        return $content;
    }

    public function hasExpired(): bool
    {
        $this->context(app(CurrentSchool::class)->id());
        $cutoff = now()->subDays($this->retentionDays());

        return AiQuarantineSnapshot::where(fn ($q) => $q->where('expires_at', '<=', now())->orWhere('created_at', '<=', $cutoff))->exists()
            || AiFlag::whereNull('quarantine_snapshot_id')->where(fn ($q) => $q->whereNull('created_at')->orWhere('created_at', '<=', $cutoff))->exists();
    }

    /** Expiry precedes deletion; consumers must never read an expired snapshot. */
    public function prune(): int
    {
        $schoolId = app(CurrentSchool::class)->id();
        $this->context($schoolId);
        $cutoff = now()->subDays($this->retentionDays());
        $ids = AiQuarantineSnapshot::where(fn ($q) => $q->where('expires_at', '<=', now())->orWhere('created_at', '<=', $cutoff))
            ->limit(500)->pluck('ai_usage_id')->unique()->all();
        // Older code-only flags contain no content but still have bounded retention.
        $ids = array_unique([...$ids, ...AiFlag::whereNull('quarantine_snapshot_id')
            ->where(fn ($q) => $q->whereNull('created_at')->orWhere('created_at', '<=', $cutoff))->limit(500)->pluck('ai_usage_id')->all()]);
        $deleted = 0;
        foreach ($ids as $id) {
            $deleted += DB::transaction(function () use ($id, $schoolId, $cutoff): int {
                School::whereKey($schoolId)->lockForUpdate()->firstOrFail();
                $this->context($schoolId);
                $usage = AiUsage::lockForUpdate()->findOrFail($id);
                $expired = AiQuarantineSnapshot::where('ai_usage_id', $id)->where(fn ($q) => $q->where('expires_at', '<=', now())->orWhere('created_at', '<=', $cutoff))->pluck('id')->all();
                $flags = AiFlag::where('ai_usage_id', $id)->where(fn ($q) => $q->whereIn('quarantine_snapshot_id', $expired)
                    ->orWhere(fn ($old) => $old->whereNull('quarantine_snapshot_id')->where(fn ($date) => $date->whereNull('created_at')->orWhere('created_at', '<=', $cutoff))));
                $count = $flags->delete();
                AiQuarantineSnapshot::whereIn('id', $expired)->delete();
                if (! AiFlag::where('ai_usage_id', $id)->exists()) {
                    $class = config('ai.quarantine.participants.'.$usage->feature);
                    if ($class !== null) {
                        if (! is_string($class) || ! is_a($class, QuarantineParticipant::class, true)) {
                            throw new AiUnavailable('quarantine_participant_unavailable');
                        }
                        app(CurrentSchool::class)->run($schoolId, function () use ($class, $usage, $schoolId): void {
                            app($class)->purgeForUsage($usage);
                            $this->context($schoolId);
                        });
                    }
                    $usage->update(['inference_output' => null, 'inference_sources' => null, 'quarantine_purged_at' => now()]);
                }

                return $count;
            });
        }

        return $deleted;
    }
}
