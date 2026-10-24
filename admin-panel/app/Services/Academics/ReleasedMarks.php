<?php

declare(strict_types=1);

namespace App\Services\Academics;

use App\Models\Academics\GradeLevel;
use App\Models\Academics\MarkRelease;
use App\Models\Academics\MarkReleaseEntry;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Academics\Transitions\LearnerAcademicAccess;
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
        $own = in_array('Student', $roles, true) && (int) $learner->user_id === (int) $fresh->id && app(LearnerAcademicAccess::class)->allows($learner);
        $guardian = in_array('Parent', $roles, true) && app(GuardianService::class)->guardianUserIds($learner)->contains((int) $fresh->id);
        abort_unless($own || $guardian, 404);

        return $learner;
    }

    public function periods(User $actor, int $learnerId): array
    {
        $this->learner($actor, $learnerId);
        $rows = MarkRelease::query()->select('session_year_id', 'term_id', 'period_key')
            ->selectRaw('MAX(created_at) as released_at')->whereHas('entries', fn ($q) => $q->where('learner_id', $learnerId))
            ->groupBy('session_year_id', 'term_id', 'period_key')->orderByDesc('released_at')->limit(200)->get();
        $years = SessionYear::withTrashed()->whereIn('id', $rows->pluck('session_year_id'))->get()->keyBy('id');
        $terms = Term::withTrashed()->whereIn('id', $rows->pluck('term_id')->filter())->get()->keyBy('id');

        return ['learner_id' => $learnerId, 'periods' => $rows->map(fn ($row) => [
            'session_year_id' => (int) $row->session_year_id, 'year_name' => $years->get($row->session_year_id)?->name ?? __('Archived school year'),
            'term_id' => $row->term_id === null ? null : (int) $row->term_id, 'term_name' => $terms->get($row->term_id)?->name,
            'released_at' => (string) $row->released_at])->all()];
    }

    public function list(User $actor, int $learnerId, int $yearId, ?int $termId): array
    {
        $this->learner($actor, $learnerId);
        SessionYear::withTrashed()->findOrFail($yearId);
        if ($termId !== null) {
            Term::withTrashed()->where('session_year_id', $yearId)->findOrFail($termId);
        }
        $entries = MarkReleaseEntry::where('learner_id', $learnerId)->whereHas('release', fn ($q) => $q->where('session_year_id', $yearId)
            ->where('period_key', $termId === null ? 'year' : 'term:'.$termId))->with('snapshot', 'release')->orderByDesc('id')->get();

        $subjects = Subject::withTrashed()->whereIn('id', $entries->map(fn ($e) => $e->release->subject_id))->get()->keyBy('id');
        $grades = GradeLevel::whereIn('id', $entries->map(fn ($e) => $e->release->grade_level_id))->get()->keyBy('id');

        return ['learner_id' => $learnerId, 'results' => $entries->unique(fn ($entry) => $entry->release->subject_id.':'.$entry->release->grade_level_id)->map(function ($entry) use ($subjects, $grades) {
            $snapshot = $entry->snapshot;
            $release = $entry->release;
            $running = $release->mode === 'running';

            return ['subject_name' => $subjects->get($release->subject_id)?->name ?? __('Archived subject'), 'grade_name' => $grades->get($release->grade_level_id)?->name ?? __('Grade'), 'release_id' => (int) $release->id, 'subject_id' => (int) $release->subject_id, 'grade_level_id' => (int) $release->grade_level_id,
                'session_year_id' => (int) $release->session_year_id, 'term_id' => $release->term_id === null ? null : (int) $release->term_id,
                'mode' => $release->mode, 'provisional' => $running, 'percentage_bp' => $running ? $snapshot->running_bp : $snapshot->percentage_bp,
                'rating_level' => $running ? $snapshot->result['running_rating_level'] : $snapshot->rating_level,
                'coverage_bp' => $snapshot->coverage_bp, 'released_at' => $release->created_at->toISOString()];
        })->values()->all()];
    }
}
