<?php

declare(strict_types=1);

namespace App\Services\Growth;

use App\Models\Academics\MarkReleaseEntry;
use App\Models\Academics\TeachingGroupMembership;
use App\Models\Growth\ProgressBandSettings;
use App\Models\Platform\School;
use App\Models\User;
use App\Policies\Academics\MarksPolicy;
use App\Policies\Growth\LearnerNotesPolicy;
use App\Services\Academics\AcademicAuthority;
use App\Services\Academics\MarkSnapshots;
use App\Services\Academics\ReleasedMarks;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProgressBands
{
    public function settings(User $actor): array
    {
        app(LearnerNotesPolicy::class)->enabled();
        app(AcademicAuthority::class)->staff($actor);

        return $this->configuration();
    }

    private function configuration(): array
    {
        app(LearnerNotesPolicy::class)->enabled();
        $row = ProgressBandSettings::query()->first();

        return ['high_bp' => $row?->high_bp ?? 8000, 'secure_bp' => $row?->secure_bp ?? 6500,
            'minimum_coverage_bp' => $row?->minimum_coverage_bp ?? 2500, 'revision' => $row?->revision ?? 0];
    }

    public function configure(User $actor, array $input): array
    {
        app(LearnerNotesPolicy::class)->enabled(true);
        app(AcademicAuthority::class)->manage($actor);
        $data = Validator::make($input, ['high_bp' => ['required', 'integer', 'between:1,10000'],
            'secure_bp' => ['required', 'integer', 'between:0,9999', 'lt:high_bp'],
            'minimum_coverage_bp' => ['required', 'integer', 'between:1,10000'], 'expected_revision' => ['required', 'integer', 'min:0']])->validate();

        return DB::transaction(function () use ($actor, $data) {
            School::query()->lockForUpdate()->findOrFail(app(CurrentSchool::class)->id());
            app(LearnerNotesPolicy::class)->enabled(true);
            app(AcademicAuthority::class)->manage($actor);
            $row = ProgressBandSettings::query()->lockForUpdate()->first();
            abort_unless((int) ($row?->revision ?? 0) === (int) $data['expected_revision'], 409, 'Progress bands changed. Reload before saving.');
            $row ??= new ProgressBandSettings;
            $row->fill(['high_bp' => (int) $data['high_bp'], 'secure_bp' => (int) $data['secure_bp'],
                'minimum_coverage_bp' => (int) $data['minimum_coverage_bp'], 'revision' => (int) ($row->revision ?? 0) + 1])->save();

            return $this->configuration();
        });
    }

    public function classify(?int $runningBp, int $coverageBp): array
    {
        abort_unless(($runningBp === null || ($runningBp >= 0 && $runningBp <= 10000)) && $coverageBp >= 0 && $coverageBp <= 10000, 422);
        $settings = $this->configuration();
        $enough = $runningBp !== null && $coverageBp >= $settings['minimum_coverage_bp'];
        $band = ! $enough ? 'insufficient_evidence' : ($runningBp >= $settings['high_bp'] ? 'high' : ($runningBp >= $settings['secure_bp'] ? 'secure' : 'support'));

        return ['band' => $band, 'percentage_bp' => $runningBp, 'coverage_bp' => $coverageBp, 'alerts_eligible' => $enough,
            'settings_revision' => $settings['revision']];
    }

    public function staff(User $actor, int $groupId, int $learnerId, int $termId): array
    {
        $group = app(MarksPolicy::class)->group($actor, $groupId, 'read');
        app(LearnerNotesPolicy::class)->academic($actor, $learnerId, (int) $group->subject_id, (int) $group->session_year_id);
        abort_unless(TeachingGroupMembership::query()->where('teaching_group_id', $groupId)->where('learner_id', $learnerId)->exists(), 404);
        app(MarkSnapshots::class)->recompute($groupId, $termId);
        $snapshot = app(MarkSnapshots::class)->query($group, $learnerId, $termId)->orderByDesc('version')->firstOrFail();

        return ['audience' => 'staff', 'learner_id' => $learnerId, 'subject_id' => (int) $group->subject_id,
            'session_year_id' => (int) $group->session_year_id, 'term_id' => $termId, 'snapshot_id' => (int) $snapshot->id,
            'provisional' => true] + $this->classify($snapshot->running_bp, $snapshot->coverage_bp);
    }

    public function family(User $actor, int $learnerId, int $yearId, ?int $termId): array
    {
        app(LearnerNotesPolicy::class)->enabled();
        $learner = app(ReleasedMarks::class)->learner($actor, $learnerId);
        abort_unless(app(ApprovalService::class)->approved($learner), 403, 'Guardian approval required.');
        $released = app(ReleasedMarks::class)->list($actor, $learnerId, $yearId, $termId);

        $results = [];
        foreach ($released['results'] as $row) {
            $entry = MarkReleaseEntry::query()->where('learner_id', $learnerId)->where('mark_release_id', $row['release_id'])->with('snapshot')->firstOrFail();
            // Official totals may include not-yet-assessed weight. Bands always
            // use the running mark, from the same frozen released snapshot.
            $results[] = array_replace($row, ['released_percentage_bp' => $row['percentage_bp']],
                $this->classify($entry->snapshot->running_bp, $entry->snapshot->coverage_bp));
        }

        return ['audience' => 'family', 'learner_id' => $learnerId, 'results' => $results];
    }
}
