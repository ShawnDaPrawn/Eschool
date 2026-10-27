<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Models\Platform\GuardianLink;
use App\Models\Platform\SchoolMembership;
use App\Models\User;
use App\Services\Platform\Tenancy\CurrentSchool;

final class FamilyHolders
{
    /** Batch current eligibility so a school preview does not query per family. */
    public function snapshots(array $sets): array
    {
        $ids = [];
        $learners = [];
        foreach ($sets as $set) {
            $ids = [...$ids, ...array_column($set['holders'], 'user_id')];
            $learners = [...$learners, ...$set['learner_ids']];
        }
        $ids = array_values(array_unique($ids));
        $learners = array_values(array_unique($learners));
        $school = app(CurrentSchool::class)->id();
        $people = User::whereIn('id', $ids)->where('status', 1)->lockForUpdate()->get(['id', 'first_name', 'last_name'])->keyBy('id');
        $members = SchoolMembership::active()->where('school_id', $school)->whereIn('user_id', $ids)->where('role', 'Parent')->lockForUpdate()->pluck('user_id')->flip();
        $links = GuardianLink::whereIn('guardian_user_id', $ids)->whereIn('learner_id', $learners)->where('status', 'active')->where('can_see_fees', true)->lockForUpdate()->get(['guardian_user_id', 'learner_id']);
        $visible = [];
        foreach ($links as $link) {
            $visible[(int) $link->guardian_user_id][(int) $link->learner_id] = true;
        }
        $result = [];
        foreach ($sets as $key => $set) {
            $holders = [];
            $issues = [];
            foreach ($set['holders'] as $h) {
                $id = (int) $h['user_id'];
                $person = $people->get($id);
                if ($person === null || ! $members->has($id)) {
                    $issues[] = 'A holder needs an active Parent membership in this school.';
                }
                foreach ($set['learner_ids'] as $learner) {
                    if (! isset($visible[$id][(int) $learner])) {
                        $issues[] = 'Each holder needs active fee visibility for every learner on this account.';
                    }
                }
                $holders[] = ['user_id' => $id, 'name' => $person === null ? '' : trim($person->first_name.' '.$person->last_name), 'share_bp' => (int) $h['share_bp']];
            }
            if ($holders === [] || array_sum(array_column($holders, 'share_bp')) !== 10000 || min(array_column($holders, 'share_bp')) < 1) {
                $issues[] = 'Account holder shares must total exactly 100%.';
            }
            usort($holders, fn ($a, $b) => $a['user_id'] <=> $b['user_id']);
            $result[$key] = ['holders' => $holders, 'issues' => array_values(array_unique($issues))];
        }

        return $result;
    }
}
