<?php

declare(strict_types=1);

namespace App\Services\Academics\Transitions;

use App\Models\User;
use App\Services\Academics\TeachingGroups;
use App\Services\People\EnrolmentService;

class JoinLearner
{
    public function apply(User $actor, array $context, int $operation): array
    {
        $data = $context['data'];
        $enrolment = app(EnrolmentService::class)->place($data['learner_id'], (int) $context['room']->id, (int) $context['year']->id, $data['effective_on'], 'Dated join #'.$operation);
        $memberships = [];
        foreach ($context['groups'] as $group) {
            $memberships[] = (int) app(TeachingGroups::class)->dated($actor, 'memberships', ['learner_id' => $data['learner_id'], 'starts_on' => $data['effective_on'],
                'ends_on' => $enrolment->ends_on->toDateString(), 'mark_rule' => $context['rule'], 'reason' => 'Dated join #'.$operation], (int) $group->id)->id;
        }

        return ['enrolment_id' => (int) $enrolment->id, 'membership_ids' => $memberships, 'fee_prorata_requested' => $data['fee_prorata_requested'],
            'fee_prorata_from_on' => $data['fee_prorata_requested'] ? $data['effective_on'] : null, 'billing_posted' => false, 'mark_rule' => $context['rule']];
    }
}
