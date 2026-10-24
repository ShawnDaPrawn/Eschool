<?php

declare(strict_types=1);

namespace App\Services\Academics;

use App\Models\Academics\TeachingGroupMembership;
use App\Models\ClassSection;
use App\Models\People\Enrolment;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Events\AcademicAssignmentChanged;
use App\Services\Platform\Events\Outbox;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;

/** End incompatible memberships when an enrolment moves; retain prior history. */
class AcademicMemberships
{
    public function reconcileLearner(int $learnerId, string $fromDate): void
    {
        $memberships = TeachingGroupMembership::query()->with('group')->where('learner_id', $learnerId)
            ->whereDate('ends_on', '>=', $fromDate)->lockForUpdate()->get();
        foreach ($memberships as $membership) {
            $group = $membership->group;
            if ($group === null) {
                continue;
            }
            $cursor = max($fromDate, $membership->starts_on->toDateString());
            $end = $membership->ends_on->toDateString();
            $entries = Enrolment::query()->where('learner_id', $learnerId)->where('session_year_id', $group->session_year_id)
                ->whereDate('starts_on', '<=', $end)->where(fn ($q) => $q->whereNull('ends_on')->orWhereDate('ends_on', '>=', $cursor))->orderBy('starts_on')->get();
            foreach ($entries as $entry) {
                if ($entry->starts_on->toDateString() > $cursor) {
                    break;
                }
                $room = ClassSection::query()->with('class')->find($entry->class_section_id);
                if ($room === null || (int) $room->class?->grade_level_id !== (int) $group->grade_level_id
                    || ($group->class_section_id !== null && (int) $room->id !== (int) $group->class_section_id)) {
                    break;
                }
                $last = $entry->ends_on?->toDateString() ?? $end;
                $cursor = CarbonImmutable::parse($last)->addDay()->toDateString();
                if ($cursor > $end) {
                    break;
                }
            }
            if ($cursor > $end) {
                continue;
            }
            $before = app(TeachingGroups::class)->datedData($membership);
            $membership->revision++;
            if ($cursor <= $membership->starts_on->toDateString()) {
                $membership->save();
                $membership->delete();
            } else {
                $membership->update(['ends_on' => CarbonImmutable::parse($cursor)->subDay()->toDateString()]);
            }
            Audit::record('academics.membership_enrolment_reconciled', $membership, $before,
                app(TeachingGroups::class)->datedData($membership) + ['deleted' => $membership->trashed(), 'reason' => 'Enrolment grade or homeroom changed']);
            Outbox::record(new AcademicAssignmentChanged(app(CurrentSchool::class)->id(), 'memberships', (int) $membership->id, (int) $group->id));
        }
    }
}
