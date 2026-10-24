<?php

declare(strict_types=1);

namespace App\Services\Academics\Transitions;

use App\Models\Academics\AcademicTransition;
use App\Models\Academics\AssessmentMark;
use App\Models\Academics\TeachingGroup;
use App\Models\Academics\TeachingGroupMembership;
use App\Models\ClassSection;
use App\Models\People\Enrolment;
use App\Models\SessionYear;
use App\Models\User;
use App\Policies\Academics\TransitionPolicy;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

class TransitionContext
{
    public function prepare(User $actor, array $input): array
    {
        $data = app(TransitionInput::class)->normalise($input);
        $learner = app(TransitionPolicy::class)->learner($actor, $data['learner_id']);
        $year = SessionYear::findOrFail($data['session_year_id']);
        abort_if(! $year->start_date || ! $year->end_date, 409, __('Set the school year dates first.'));
        $start = substr((string) $year->start_date, 0, 10);
        $end = substr((string) $year->end_date, 0, 10);
        if ($data['effective_on'] < $start || $data['effective_on'] > $end) {
            throw ValidationException::withMessages(['effective_on' => __('Choose a date within the selected school year.')]);
        }
        $source = $data['kind'] === 'subject_change' ? TeachingGroupMembership::with('group')->where('learner_id', $learner->id)->findOrFail($data['source_membership_id']) : null;
        $target = null;
        $cover = Enrolment::where('learner_id', $learner->id)->onDate($data['effective_on'])->first();
        if ($data['kind'] === 'join') {
            abort_if($cover !== null, 409, __('This learner is already enrolled on that date. Use a dated homeroom or subject change.'));
            $room = ClassSection::with('class.gradeLevel', 'section')->findOrFail($data['class_section_id']);
        } else {
            abort_if($cover === null || (int) $cover->session_year_id !== (int) $year->id, 409, __('No enrolment in this year covers the change date.'));
            $room = ClassSection::with('class.gradeLevel', 'section')->findOrFail($cover->class_section_id);
        }
        abort_if($data['kind'] !== 'leave' && $room->class?->grade_level_id === null, 409, __('Map the homeroom to a grade first.'));
        $grade = $room->class?->grade_level_id === null ? null : (int) $room->class->grade_level_id;
        $policy = $data['kind'] === 'leave' ? null : app(TransitionPolicies::class)->grade($grade);
        $rule = $policy === null ? null : ($data['kind'] === 'join' ? $policy['join_mark_rule'] : $policy['change_mark_rule']);
        $rule = $data['mark_rule_override'] ?? $rule;
        $groups = [];
        if ($data['kind'] === 'join') {
            if (! in_array($rule, ['catch_up', 'excused'], true)) {
                throw ValidationException::withMessages(['mark_rule_override' => __('Choose catch-up or excused for earlier join tasks.')]);
            }
            foreach ($data['group_ids'] as $id) {
                $groups[] = $this->target($id, (int) $year->id, $grade, (int) $room->id);
            }
            $next = Enrolment::where('learner_id', $learner->id)->whereDate('starts_on', '>', $data['effective_on'])->orderBy('starts_on')->first();
            $end = $next === null ? $end : min($end, CarbonImmutable::parse($next->starts_on)->subDay()->toDateString());
        } elseif ($data['kind'] === 'subject_change') {
            abort_unless($source->group !== null && (int) $source->group->session_year_id === (int) $year->id && (int) $source->group->grade_level_id === $grade
                && $source->starts_on->toDateString() <= $data['effective_on'] && $source->ends_on->toDateString() >= $data['effective_on'], 409, __('The source membership no longer covers this grade and date.'));
            $target = $this->target($data['target_group_id'], (int) $year->id, $grade, (int) $room->id);
            abort_if((int) $source->teaching_group_id === (int) $target->id, 422, __('Choose a different teaching group.'));
            if ($rule === 'carried' && (int) $source->group->subject_id !== (int) $target->subject_id) {
                throw ValidationException::withMessages(['mark_rule_override' => __('Carried marks require the same subject. Explicitly choose catch-up or excused for a different subject.')]);
            }
            if ($source->starts_on->toDateString() === $data['effective_on'] && AssessmentMark::where('learner_id', $learner->id)->where('teaching_group_id', $source->teaching_group_id)->exists()) {
                abort(409, __('Keep the captured membership in history. Choose a later effective date.'));
            }
            $end = min($end, $source->ends_on->toDateString());
            $groups = [$target];
        } else {
            abort_if(AcademicTransition::where('learner_id', $learner->id)->where('kind', 'leave')->whereDate('effective_on', $data['effective_on'])->exists(), 409, __('This departure is already recorded. Open its exit package.'));
        }
        $state = $this->state($data['learner_id']);
        $fingerprint = hash('sha256', json_encode([$data, $state, $policy, ['year_starts_on' => $start, 'year_ends_on' => substr((string) $year->end_date, 0, 10), 'membership_ends_on' => $end], ['room_id' => $room->id, 'grade_id' => $grade], array_map(fn ($g) => [$g->id, $g->revision, $g->active, $g->class_section_id, $g->subject_id], $groups)], JSON_THROW_ON_ERROR));

        return compact('data', 'learner', 'year', 'room', 'grade', 'policy', 'rule', 'groups', 'source', 'target', 'end', 'state', 'fingerprint');
    }

    private function target(int $id, int $year, int $grade, int $room): TeachingGroup
    {
        $group = TeachingGroup::with('subject')->findOrFail($id);
        abort_unless($group->active && (int) $group->session_year_id === $year && (int) $group->grade_level_id === $grade && ($group->class_section_id === null || (int) $group->class_section_id === $room), 422, __('Choose an active group in this year and homeroom grade.'));

        return $group;
    }

    public function state(int $learner): array
    {
        return ['enrolments' => Enrolment::withTrashed()->where('learner_id', $learner)->orderBy('starts_on')->orderBy('id')->get()->map(fn ($e) => ['id' => (int) $e->id, 'session_year_id' => (int) $e->session_year_id,
            'class_section_id' => (int) $e->class_section_id, 'starts_on' => $e->starts_on->toDateString(), 'ends_on' => $e->ends_on?->toDateString(), 'cancelled_at' => $e->deleted_at?->toISOString()])->all(),
            'memberships' => TeachingGroupMembership::withTrashed()->with('group')->where('learner_id', $learner)->orderBy('starts_on')->orderBy('id')->get()->map(fn ($m) => ['id' => (int) $m->id, 'teaching_group_id' => (int) $m->teaching_group_id,
                'session_year_id' => $m->group === null ? null : (int) $m->group->session_year_id, 'grade_level_id' => $m->group === null ? null : (int) $m->group->grade_level_id, 'revision' => $m->revision,
                'starts_on' => $m->starts_on->toDateString(), 'ends_on' => $m->ends_on->toDateString(), 'mark_rule' => $m->mark_rule, 'cancelled_at' => $m->deleted_at?->toISOString()])->all(),
            'changes' => AcademicTransition::where('learner_id', $learner)->orderBy('id')->get(['id', 'kind', 'effective_on'])->map(fn ($t) => ['id' => (int) $t->id, 'kind' => $t->kind, 'effective_on' => $t->effective_on->toDateString()])->all()];
    }

    public function preview(array $context): array
    {
        return ['input' => $context['data'], 'fingerprint' => $context['fingerprint'], 'learner_name' => trim($context['learner']->user?->first_name.' '.$context['learner']->user?->last_name),
            'grade_level_id' => $context['grade'], 'homeroom_name' => trim($context['room']->class->name.' '.$context['room']->section?->name), 'policy' => $context['policy'], 'mark_rule' => $context['data']['kind'] === 'leave' ? null : $context['rule'],
            'membership_ends_on' => $context['end'], 'group_assignments' => array_map(fn ($g) => ['id' => (int) $g->id, 'name' => $g->name, 'subject_name' => $g->subject->name], $context['groups']), 'state' => $context['state']];
    }
}
