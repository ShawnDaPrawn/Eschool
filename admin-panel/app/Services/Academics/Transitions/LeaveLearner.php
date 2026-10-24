<?php

declare(strict_types=1);

namespace App\Services\Academics\Transitions;

use App\Models\Academics\TeachingGroupMembership;
use App\Models\People\Enrolment;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Events\AcademicAssignmentChanged;
use App\Services\Platform\Events\Outbox;
use App\Services\Platform\Tenancy\CurrentSchool;

class LeaveLearner
{
    public function apply(array $context): array
    {
        $data = $context['data'];
        $ended = $cancelled = $memberships = $cancelledMemberships = [];
        foreach (Enrolment::where('learner_id', $data['learner_id'])->where(fn ($q) => $q->whereNull('ends_on')->orWhereDate('ends_on', '>=', $data['effective_on']))->lockForUpdate()->get() as $row) {
            if ($row->starts_on->toDateString() > $data['effective_on']) {
                $cancelled[] = (int) $row->id;
                $row->delete();
            } else {
                $ended[] = (int) $row->id;
                $row->update(['ends_on' => $data['effective_on']]);
            }
        }
        foreach (TeachingGroupMembership::where('learner_id', $data['learner_id'])->whereDate('ends_on', '>', $data['effective_on'])->lockForUpdate()->get() as $row) {
            $before = $row->getAttributes();
            $row->revision++;
            if ($row->starts_on->toDateString() > $data['effective_on']) {
                $row->save();
                $row->delete();
                $cancelledMemberships[] = (int) $row->id;
            } else {
                $row->update(['ends_on' => $data['effective_on']]);
                $memberships[] = (int) $row->id;
            }
            Audit::record('academics.membership_departure', $row, $before, $row->getAttributes());
            Outbox::record(new AcademicAssignmentChanged(app(CurrentSchool::class)->id(), 'memberships', (int) $row->id, (int) $row->teaching_group_id));
        }

        return ['last_attended_on' => $data['effective_on'], 'ended_enrolment_ids' => $ended, 'cancelled_enrolment_ids' => $cancelled,
            'ended_membership_ids' => $memberships, 'cancelled_membership_ids' => $cancelledMemberships, 'records_deleted' => false];
    }
}
