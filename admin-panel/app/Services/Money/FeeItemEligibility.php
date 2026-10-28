<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Models\Academics\GradeLevel;
use App\Models\Academics\TeachingGroup;
use App\Models\Academics\TeachingGroupMembership;
use App\Models\Activities\ActivityGroup;
use App\Models\Activities\ActivityMembership;
use App\Models\ClassSchool;
use App\Models\ClassSection;
use App\Models\Money\FeeAccount;
use App\Models\Money\FeeItem;
use App\Models\Money\FeeItemEligibilityRule;
use App\Models\People\Enrolment;
use App\Models\Students;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

/** Dated canonical enrolments and memberships, shared by batches and one-off charges. */
final class FeeItemEligibility
{
    public function evaluate(FeeItem $item, array $learnerIds, int $year, string $date): ?array
    {
        $rule = FeeItemEligibilityRule::where('fee_item_id', $item->id)->lockForUpdate()->first();
        if ($rule === null || $rule->mode === 'manual') {
            return null;
        }
        $learners = Students::whereIn('id', $learnerIds)->orderBy('id')->lockForUpdate()->get(['id', 'user_id']);
        $context = app(FeePlanRules::class)->context($rule->applies_to, $learners->pluck('user_id')->all(), true);
        $enrolments = Enrolment::where('session_year_id', $year)->onDate($date)->whereIn('learner_id', $learnerIds)->lockForUpdate()->get()->groupBy('learner_id');
        $sections = ClassSection::whereIn('id', $enrolments->flatten()->pluck('class_section_id'))->lockForUpdate()->get(['id', 'class_id'])->keyBy('id');
        $classes = ClassSchool::whereIn('id', $sections->pluck('class_id'))->lockForUpdate()->get(['id', 'grade_level_id'])->keyBy('id');
        $phases = GradeLevel::whereIn('id', $classes->pluck('grade_level_id'))->lockForUpdate()->pluck('phase', 'id');
        [$members,$source] = $this->membership($rule, $learnerIds, $year, $date);
        $matched = [];
        $issues = [];
        foreach ($learners as $learner) {
            $links = $enrolments->get($learner->id, collect());
            if ($links->count() !== 1 || $links->first()->legacy_dates) {
                $issues[] = ['learner_id' => (int) $learner->id, 'message' => 'Confirm one dated enrolment before charging this learner'];

                continue;
            }
            $section = $sections->get($links->first()->class_section_id);
            $class = $section === null ? null : $classes->get($section->class_id);
            if ($class === null) {
                $issues[] = ['learner_id' => (int) $learner->id, 'message' => 'Homeroom needs review before charging'];

                continue;
            }
            if (($rule->target_scope === 'grade' && (int) $class->grade_level_id !== $rule->target_id) || ($rule->target_scope === 'class' && (int) $class->id !== $rule->target_id)) {
                continue;
            }
            try {
                $matches = app(FeePlanRules::class)->matches($rule->applies_to, $phases->get($class->grade_level_id), (int) $learner->user_id, $context);
            } catch (ValidationException) {
                $issues[] = ['learner_id' => (int) $learner->id, 'message' => 'Item rule inputs need review'];

                continue;
            }
            if (! $matches) {
                continue;
            }
            if ($rule->mode === 'membership' && ! isset($members[(int) $learner->id])) {
                continue;
            }
            $matched[] = (int) $learner->id;
        }

        return ['rule_id' => (int) $rule->id, 'rule_revision' => $rule->revision, 'mode' => $rule->mode, 'target_scope' => $rule->target_scope, 'target_id' => $rule->target_id, 'applies_to' => $rule->applies_to, 'matched_learner_ids' => $matched, 'issues' => $issues, 'source' => $source, 'memberships' => $members];
    }

    public function forCharge(FeeItem $item, array $learnerIds, int $year, string $date, FeeAccount $account): ?array
    {
        $result = $this->evaluate($item, $learnerIds, $year, $date);
        abort_unless($result === null || $result['issues'] === [], 422, 'Assigned learner enrolments need review before charging this item.');
        abort_unless($result === null || $result['matched_learner_ids'] !== [], 422, 'No assigned learner currently qualifies for this item on the charge date.');
        if (($result['mode'] ?? null) === 'opt_in') {
            $result['consents'] = app(FeeItemConsents::class)->authorizes($item, $account, $year, $learnerIds, $date);
        }

        return $result;
    }

    /** @template TModel of Model
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    private function dated(Builder $query, string $date, bool $active = true): Builder
    {
        return $query->when($active, fn ($q) => $q->where('active', true))->where('starts_on', '<=', $date)->where(fn ($q) => $q->whereNull('ends_on')->orWhere('ends_on', '>=', $date));
    }

    private function membership(FeeItemEligibilityRule $rule, array $ids, int $year, string $date): array
    {
        if ($rule->mode !== 'membership') {
            return [[], null];
        }
        if ($rule->activity_group_id !== null) {
            $groups = $this->dated(ActivityGroup::whereKey($rule->activity_group_id)->where('session_year_id', $year), $date)->lockForUpdate()->get(['id', 'revision']);
            $links = $this->dated(ActivityMembership::whereIn('activity_group_id', $groups->pluck('id'))->whereIn('learner_id', $ids), $date)->orderBy('id')->lockForUpdate()->get(['id', 'learner_id', 'revision']);
            $kind = 'activity_group';
            $sourceId = $rule->activity_group_id;
        } else {
            $query = TeachingGroup::where('session_year_id', $year);
            $rule->teaching_group_id !== null ? $query->whereKey($rule->teaching_group_id) : $query->where('subject_id', $rule->subject_id);
            $groups = $query->where('active', true)->orderBy('id')->lockForUpdate()->get(['id', 'revision']);
            $links = $this->dated(TeachingGroupMembership::whereIn('teaching_group_id', $groups->pluck('id'))->whereIn('learner_id', $ids), $date, false)->orderBy('id')->lockForUpdate()->get(['id', 'learner_id', 'revision']);
            $kind = $rule->teaching_group_id !== null ? 'teaching_group' : 'subject';
            $sourceId = $rule->teaching_group_id ?? $rule->subject_id;
        }
        $members = [];
        foreach ($links as $link) {
            $members[(int) $link->learner_id][] = ['id' => (int) $link->id, 'revision' => (int) $link->revision];
        }

        return [$members, ['kind' => $kind, 'id' => $sourceId, 'groups' => $groups->map(fn ($g) => ['id' => (int) $g->id, 'revision' => (int) $g->revision])->all()]];
    }
}
