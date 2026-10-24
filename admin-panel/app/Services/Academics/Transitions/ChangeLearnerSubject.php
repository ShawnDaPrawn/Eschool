<?php

declare(strict_types=1);

namespace App\Services\Academics\Transitions;

use App\Models\User;
use App\Services\Academics\TeachingGroups;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Events\AcademicAssignmentChanged;
use App\Services\Platform\Events\Outbox;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;

class ChangeLearnerSubject
{
    public function apply(User $actor, array $context, int $operation): array
    {
        $source = $context['source'];
        $before = $source->getAttributes();
        $source->revision++;
        if ($source->starts_on->toDateString() === $context['data']['effective_on']) {
            $source->save();
            $source->delete();
        } else {
            $source->update(['ends_on' => CarbonImmutable::parse($context['data']['effective_on'])->subDay()->toDateString()]);
        }
        Audit::record('academics.membership_subject_changed', $source, $before, $source->getAttributes());
        Outbox::record(new AcademicAssignmentChanged(app(CurrentSchool::class)->id(), 'memberships', (int) $source->id, (int) $source->teaching_group_id));
        $target = app(TeachingGroups::class)->dated($actor, 'memberships', ['learner_id' => $context['data']['learner_id'], 'starts_on' => $context['data']['effective_on'], 'ends_on' => $context['end'],
            'mark_rule' => $context['rule'], 'reason' => 'Dated subject change #'.$operation], (int) $context['target']->id);

        return ['source_membership_id' => (int) $source->id, 'target_membership_id' => (int) $target->id, 'mark_rule' => $context['rule'], 'marks_copied' => false,
            'canonical_marks_reused' => $context['rule'] === 'carried', 'records_deleted' => false];
    }
}
