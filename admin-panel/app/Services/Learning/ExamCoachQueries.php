<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\Academics\TeachingGroup;
use App\Models\Learning\ExamAnswer;
use App\Models\Learning\ExamCoachOperation;
use App\Models\Learning\ExamStudyPlan;
use App\Models\Learning\PastPaper;
use App\Models\Learning\PastPaperVersion;
use App\Models\User;
use App\Policies\Academics\ContentPolicy;
use App\Policies\People\ProfilePolicy;
use App\Services\Academics\AcademicAuthority;
use App\Services\Platform\Modules\ModuleGate;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/** Resolve only fresh, current-group choices; never serialize raw paper models. */
class ExamCoachQueries
{
    public function configuration(User $actor): array
    {
        $fresh = app(ExamCoachAccess::class)->actor($actor);
        $roles = app(ProfilePolicy::class)->roles($fresh);
        abort_unless(in_array('Student', $roles, true) || in_array('Teacher', $roles, true), 403);
        $staff = [];
        if (in_array('Teacher', $roles, true)) {
            $today = app(AcademicAuthority::class)->today();
            $groups = TeachingGroup::where('active', true)->whereHas('subject')->where('session_year_id', (int) schoolSetting('session_year'))
                ->whereHas('allocations', fn ($q) => $q->whereIn('teacher_id', app(AcademicAuthority::class)->teachers($fresh))
                    ->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today))->with('subject')->orderBy('name')->limit(201)->get();
            abort_if($groups->count() > 200, 422);
            foreach ($groups as $group) {
                app(ExamCoachAccess::class)->staff($fresh, (int) $group->id);
                $staff[] = $this->group($group);
            }
        }
        $learner = null;
        $family = [];
        if (in_array('Student', $roles, true)) {
            $person = app(ExamCoachAccess::class)->learner($fresh);
            $learner = (int) $person->id;
            $ids = app(ContentPolicy::class)->learnerGroups($fresh, $learner);
            $groups = TeachingGroup::whereIn('id', $ids)->whereHas('subject')->with('subject')->orderBy('name')->limit(201)->get();
            abort_if($groups->count() > 200, 422);
            foreach ($groups as $group) {
                app(ExamCoachAccess::class)->learnerGroup($fresh, (int) $group->id);
                $family[] = $this->group($group);
            }
        }
        app(ExamCoachAccess::class)->actor($fresh);

        return ['reader_id' => (int) $fresh->id, 'learner_id' => $learner, 'staff_groups' => $staff, 'learner_groups' => $family,
            'writable' => app(ModuleGate::class)->writable('exam_coach'), 'photo_feedback_enabled' => $this->photoFeedbackEnabled()];
    }

    private function photoFeedbackEnabled(): bool
    {
        $feature = config('ai.inference.features.exam_coach');

        return config('ai.inference.enabled') === true && is_array($feature) && ($feature['enabled'] ?? false) === true
            && is_string($feature['model'] ?? null) && trim($feature['model']) !== ''
            && is_int($feature['cents_per_million_tokens'] ?? null) && $feature['cents_per_million_tokens'] >= 0
            && config('ai.providers.'.($feature['provider'] ?? '').'.qualified') === true;
    }

    private function group(TeachingGroup $group): array
    {
        return ['id' => (int) $group->id, 'name' => $group->name, 'subject_name' => $group->subject->name];
    }

    public function choices(User $actor, int $groupId): array
    {
        $group = app(ExamCoachAccess::class)->staff($actor, $groupId);
        $all = $this->configuration($actor)['staff_groups'];
        $ids = array_column($all, 'id');
        $audiences = TeachingGroup::whereIn('id', $ids)->where('subject_id', $group->subject_id)->where('grade_level_id', $group->grade_level_id)
            ->with('subject')->orderBy('name')->get()->map(fn ($row) => $this->group($row))->all();
        app(ExamCoachAccess::class)->staff($actor, $groupId);

        return ['group_id' => $groupId, 'frameworks' => app(ExamCoachAccess::class)->frameworks($group),
            'topics' => array_values(app(ExamCoachAccess::class)->topics($group)), 'audience_groups' => $audiences,
            'writable' => app(ModuleGate::class)->writable('exam_coach')];
    }

    public function papers(User $actor, int $groupId, bool $staff, int $page): array
    {
        abort_unless($page >= 1 && $page <= 1000, 422);
        $access = app(ExamCoachAccess::class);
        $group = $staff ? $access->staff($actor, $groupId) : $access->learnerGroup($actor, $groupId);
        $papers = PastPaper::where('subject_id', $group->subject_id)->where('grade_level_id', $group->grade_level_id)
            ->whereIn('framework', $access->frameworks($group));
        if (! $staff) {
            $papers->where('active', true)->whereNotNull('published_version_id');
        }
        $query = PastPaperVersion::select('id', 'past_paper_id')->whereIn('past_paper_id', $papers->select('id'))->whereJsonContains('group_ids', $groupId);
        if (! $staff) {
            $query->where('state', 'published')->whereExists(fn ($q) => $q->selectRaw('1')->from('past_papers as visible_paper')
                ->whereColumn('visible_paper.id', 'past_paper_versions.past_paper_id')->whereColumn('visible_paper.published_version_id', 'past_paper_versions.id'));
        } elseif (! app(ContentPolicy::class)->leads($actor, (int) $group->grade_level_id)) {
            $query->where(fn ($q) => $q->where('state', 'published')->orWhereIn('past_paper_id', PastPaper::where('created_by', $actor->id)->select('id')));
        }
        $rows = $query->orderByDesc('created_at')->orderBy('id')->offset(($page - 1) * 25)->limit(26)->get();
        $items = [];
        foreach ($rows->take(25) as $row) {
            try {
                $data = app(PastPaperLibrary::class)->data($actor, $groupId, $row->id, $staff);
                $paper = PastPaper::findOrFail($row->past_paper_id);
                $items[] = array_intersect_key($data, array_flip(['id', 'version_id', 'number', 'title', 'paper_year', 'paper_code', 'state', 'licence_kind']))
                    + ['revision' => (int) $paper->revision, 'can_edit' => $staff && ($paper->active && ((int) $paper->created_by === (int) $actor->id || app(ContentPolicy::class)->leads($actor, (int) $group->grade_level_id)))];
            } catch (HttpExceptionInterface|ModelNotFoundException) {
                // Expired rights, old publication or revoked references never expose a label.
                continue;
            }
        }
        $staff ? $access->staff($actor, $groupId) : $access->learnerGroup($actor, $groupId);

        return ['group_id' => $groupId, 'papers' => $items, 'page' => $page, 'has_more' => $rows->count() > 25];
    }

    public function plans(User $actor, int $page): array
    {
        abort_unless($page >= 1 && $page <= 1000, 422);
        $learner = app(ExamCoachAccess::class)->learner($actor);
        $rows = ExamStudyPlan::where('user_id', $actor->id)->where('learner_id', $learner->id)->orderByDesc('created_at')->orderBy('id')
            ->offset(($page - 1) * 25)->limit(26)->get(['id']);
        $items = [];
        foreach ($rows->take(25) as $row) {
            try {
                $data = app(ExamStudyPlans::class)->read($actor, $row->id);
                $items[] = array_intersect_key($data, array_flip(['id', 'group_id', 'subject_id', 'exam_on', 'papers']));
            } catch (HttpExceptionInterface|ModelNotFoundException) {
                continue;
            }
        }
        app(ExamCoachAccess::class)->learner($actor);

        return ['plans' => $items, 'page' => $page, 'has_more' => $rows->count() > 25];
    }

    public function answers(User $actor, ?int $groupId, int $page): array
    {
        abort_unless($page >= 1 && $page <= 1000, 422);
        $staff = $groupId !== null;
        $query = ExamAnswer::query();
        if ($staff) {
            app(ExamCoachAccess::class)->staff($actor, $groupId);
            $query->where('teaching_group_id', $groupId)->where('share_with_teacher', true);
        } else {
            $learner = app(ExamCoachAccess::class)->learner($actor);
            $query->where('user_id', $actor->id)->where('learner_id', $learner->id);
        }
        $rows = $query->orderByDesc('created_at')->orderBy('id')->offset(($page - 1) * 25)->limit(26)->get(['id']);
        $items = [];
        foreach ($rows->take(25) as $meta) {
            try {
                $data = app(ExamFeedback::class)->read($actor, $meta->id, $staff);
                if (! in_array($data['state'], ['purged', 'quarantined'], true)) {
                    $item = array_intersect_key($data, array_flip(['id', 'operation_id', 'state', 'group_id', 'paper_version_id', 'question_number', 'revision', 'expires_at', 'failure_code']));
                    if ($staff) {
                        $row = app(ExamAnswerAccess::class)->answer($actor, $meta->id, true);
                        $owner = User::findOrFail($row->user_id);
                        $item['learner_id'] = (int) $row->learner_id;
                        $item['learner_name'] = trim($owner->first_name.' '.$owner->last_name);
                    }
                    $items[] = $item;
                }
            } catch (HttpExceptionInterface|ModelNotFoundException) {
                continue;
            }
        }
        $staff ? app(ExamCoachAccess::class)->staff($actor, $groupId) : app(ExamCoachAccess::class)->learner($actor);

        return ['answers' => $items, 'page' => $page, 'has_more' => $rows->count() > 25];
    }

    /** Opaque own receipt recovery survives erased local draft prose. */
    public function operation(User $actor, int $groupId, string $operation): array
    {
        $group = app(ExamCoachAccess::class)->staff($actor, $groupId);
        $receipt = ExamCoachOperation::where('actor_id', $actor->id)->where('operation_id', $operation)->firstOrFail()->result;
        if (isset($receipt['answer_id'])) {
            $answer = app(ExamAnswerAccess::class)->answer($actor, $receipt['answer_id'], true);
            abort_unless((int) $answer->teaching_group_id === $groupId, 404);

            return ['kind' => 'answer_review', 'operation_id' => $operation, 'paper' => null, 'review' => $receipt];
        }
        abort_unless(array_key_exists('version_id', $receipt) && is_int($receipt['id'] ?? null), 404);
        $paper = PastPaper::where('subject_id', $group->subject_id)->where('grade_level_id', $group->grade_level_id)->findOrFail($receipt['id']);
        abort_unless((int) $paper->created_by === (int) $actor->id || app(ContentPolicy::class)->leads($actor, (int) $group->grade_level_id), 403);
        $versionId = $receipt['version_id'];
        if ($versionId !== null) {
            $version = PastPaperVersion::select('id', 'past_paper_id', 'group_ids')->where('past_paper_id', $paper->id)->findOrFail($versionId);
            abort_unless(in_array($groupId, $version->group_ids, true), 404);
        }
        app(ExamCoachAccess::class)->staff($actor, $groupId);

        return ['kind' => 'paper', 'operation_id' => $operation, 'paper' => $receipt, 'review' => null];
    }
}
