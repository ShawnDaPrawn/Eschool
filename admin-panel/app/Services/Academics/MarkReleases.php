<?php

declare(strict_types=1);

namespace App\Services\Academics;

use App\Models\Academics\MarkRelease;
use App\Models\Academics\MarkReleaseEntry;
use App\Models\Academics\TeachingGroup;
use App\Models\Platform\School;
use App\Models\User;
use App\Policies\Academics\MarksPolicy;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Events\AcademicMarksReleased;
use App\Services\Platform\Events\Outbox;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/** Selected learners can progress independently; released evidence never changes. */
class MarkReleases
{
    public static function rules(): array
    {
        return ['term_id' => ['present', 'nullable', 'integer', 'min:1'], 'mode' => ['required', 'in:official,running'],
            'learner_ids' => ['required', 'array', 'min:1', 'max:1000'], 'learner_ids.*' => ['required', 'integer', 'min:1', 'distinct'],
            'fingerprint' => ['sometimes', 'required', 'string', 'regex:/^[a-f0-9]{64}$/']];
    }

    public function preview(User $actor, int $groupId, array $input): array
    {
        app(MarksPolicy::class)->group($actor, $groupId, 'release');
        $data = Validator::make($input, self::rules())->validate();
        $data['term_id'] = $data['term_id'] === null ? null : (int) $data['term_id'];

        return DB::transaction(function () use ($actor, $groupId, $data) {
            School::query()->lockForUpdate()->findOrFail(app(CurrentSchool::class)->id());
            $group = app(MarksPolicy::class)->group($actor, $groupId, 'release');

            return $this->evidence($group, $data);
        }, 3);
    }

    public function release(User $actor, int $groupId, array $input): array
    {
        app(MarksPolicy::class)->group($actor, $groupId, 'release');
        $rules = self::rules();
        $rules['fingerprint'][0] = 'required';
        $data = Validator::make($input, $rules)->validate();
        $data['term_id'] = $data['term_id'] === null ? null : (int) $data['term_id'];

        return DB::transaction(function () use ($actor, $groupId, $data) {
            School::query()->lockForUpdate()->findOrFail(app(CurrentSchool::class)->id());
            $group = app(MarksPolicy::class)->group($actor, $groupId, 'release');
            $preview = $this->evidence($group, $data);
            abort_unless(hash_equals($preview['fingerprint'], $data['fingerprint']), 409, __('Results changed. Preview the new evidence before releasing.'));
            abort_unless($preview['can_release'], 409, __('Complete the selected results or explicitly release a provisional running mark.'));
            $latest = $this->query($group, $data['term_id'])->orderByDesc('version')->first();
            if ($latest?->fingerprint === $preview['fingerprint']) {
                return $this->data($latest);
            }
            $release = MarkRelease::create(['subject_id' => $group->subject_id, 'grade_level_id' => $group->grade_level_id, 'session_year_id' => $group->session_year_id,
                'term_id' => $data['term_id'], 'period_key' => $this->period($data['term_id']), 'mode' => $data['mode'], 'version' => (int) ($latest?->version ?? 0) + 1,
                'fingerprint' => $preview['fingerprint'], 'released_by' => $actor->id, 'created_at' => now()]);
            foreach ($preview['learners'] as $row) {
                MarkReleaseEntry::create(['mark_release_id' => $release->id, 'learner_id' => $row['learner_id'], 'mark_snapshot_id' => $row['snapshot_id'], 'created_at' => now()]);
            }
            app(MarkExamProjection::class)->project($group, $release);
            Audit::record('academics.marks_released', $release, [], ['fingerprint' => $preview['fingerprint'], 'mode' => $release->mode, 'learner_ids' => $data['learner_ids']]);
            Outbox::record(new AcademicMarksReleased(app(CurrentSchool::class)->id(), (int) $release->id));

            return $this->data($release);
        }, 3);
    }

    private function evidence(TeachingGroup $group, array $data): array
    {
        $ids = array_map('intval', $data['learner_ids']);
        sort($ids);
        $members = app(MarkInputs::class)->memberships($group)->pluck('learner_id')->unique()->map(fn ($id) => (int) $id)->all();
        abort_unless(array_diff($ids, $members) === [], 404);
        app(MarkSnapshots::class)->recompute((int) $group->id, $data['term_id']);
        $rows = [];
        $can = true;
        foreach ($ids as $id) {
            $snapshot = app(MarkSnapshots::class)->query($group, $id, $data['term_id'])->orderByDesc('version')->firstOrFail();
            $ready = $data['mode'] === 'official' ? $snapshot->complete : $snapshot->running_bp !== null;
            $can = $can && $ready;
            $rows[] = ['learner_id' => $id, 'snapshot_id' => (int) $snapshot->id, 'fingerprint' => $snapshot->fingerprint,
                'percentage_bp' => $data['mode'] === 'official' ? $snapshot->percentage_bp : $snapshot->running_bp,
                'rating_level' => $data['mode'] === 'official' ? $snapshot->rating_level : $snapshot->result['running_rating_level'],
                'coverage_bp' => $snapshot->coverage_bp, 'complete' => $snapshot->complete, 'ready' => $ready];
        }
        $fingerprint = hash('sha256', json_encode(['subject' => (int) $group->subject_id, 'grade' => (int) $group->grade_level_id,
            'year' => (int) $group->session_year_id, 'period' => $this->period($data['term_id']), 'mode' => $data['mode'], 'learners' => $rows], JSON_THROW_ON_ERROR));

        return ['mode' => $data['mode'], 'provisional' => $data['mode'] === 'running', 'fingerprint' => $fingerprint, 'can_release' => $can, 'learners' => $rows];
    }

    public function query(TeachingGroup $group, ?int $term): Builder
    {
        return MarkRelease::query()->where('subject_id', $group->subject_id)->where('grade_level_id', $group->grade_level_id)
            ->where('session_year_id', $group->session_year_id)->where('period_key', $this->period($term));
    }

    public function period(?int $term): string
    {
        return $term === null ? 'year' : 'term:'.$term;
    }

    public function data(MarkRelease $release): array
    {
        return ['id' => (int) $release->id, 'subject_id' => (int) $release->subject_id, 'grade_level_id' => (int) $release->grade_level_id,
            'session_year_id' => (int) $release->session_year_id, 'term_id' => $release->term_id === null ? null : (int) $release->term_id,
            'mode' => $release->mode, 'provisional' => $release->mode === 'running', 'version' => $release->version,
            'fingerprint' => $release->fingerprint, 'released_at' => $release->created_at->toISOString(),
            'learner_ids' => $release->entries()->pluck('learner_id')->map(fn ($id) => (int) $id)->all()];
    }
}
