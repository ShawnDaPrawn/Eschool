<?php

declare(strict_types=1);

namespace App\Services\Academics;

use App\Models\Academics\MarkReleaseEntry;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\Term;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Platform\Identity\GuardianService;

/** Family reads contain released evidence only, with fresh guardian rights. */
class ReleasedMarks
{
    public function learner(User $actor, int $learnerId): Students
    {
        $policy = app(ProfilePolicy::class);
        $fresh = $policy->actor($actor);
        $learner = Students::findOrFail($learnerId);
        $roles = $policy->roles($fresh);
        $own = in_array('Student', $roles, true) && (int) $learner->user_id === (int) $fresh->id;
        $guardian = in_array('Parent', $roles, true) && app(GuardianService::class)->guardianUserIds($learner)->contains((int) $fresh->id);
        abort_unless($own || $guardian, 404);

        return $learner;
    }

    public function list(User $actor, int $learnerId, int $yearId, ?int $termId): array
    {
        $this->learner($actor, $learnerId);
        SessionYear::findOrFail($yearId);
        if ($termId !== null) {
            Term::where('session_year_id', $yearId)->findOrFail($termId);
        }
        $entries = MarkReleaseEntry::where('learner_id', $learnerId)->whereHas('release', fn ($q) => $q->where('session_year_id', $yearId)
            ->where('period_key', $termId === null ? 'year' : 'term:'.$termId))->with('snapshot', 'release')->orderByDesc('id')->get();

        return ['learner_id' => $learnerId, 'results' => $entries->unique(fn ($entry) => $entry->release->subject_id.':'.$entry->release->grade_level_id)->map(function ($entry) {
            $snapshot = $entry->snapshot;
            $release = $entry->release;
            $running = $release->mode === 'running';

            return ['release_id' => (int) $release->id, 'subject_id' => (int) $release->subject_id, 'grade_level_id' => (int) $release->grade_level_id,
                'session_year_id' => (int) $release->session_year_id, 'term_id' => $release->term_id === null ? null : (int) $release->term_id,
                'mode' => $release->mode, 'provisional' => $running, 'percentage_bp' => $running ? $snapshot->running_bp : $snapshot->percentage_bp,
                'rating_level' => $running ? $snapshot->result['running_rating_level'] : $snapshot->rating_level,
                'coverage_bp' => $snapshot->coverage_bp, 'released_at' => $release->created_at->toISOString()];
        })->values()->all()];
    }
}
