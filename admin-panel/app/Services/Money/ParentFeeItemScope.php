<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Models\Money\FeeAccount;
use App\Models\Money\FeeAccountHolder;
use App\Models\Money\FeeAccountLearner;
use App\Models\Money\FeeItem;
use App\Models\Platform\GuardianLink;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\User;
use App\Policies\Money\ParentFeePolicy;
use App\Services\People\Leavers\LeaverState;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;

final class ParentFeeItemScope
{
    /** Caller holds a transaction; ParentFeePolicy takes the school lock first. */
    public function account(User $actor, int $accountId, ?int $yearId = null, bool $write = false): array
    {
        app(ParentFeePolicy::class)->authorize($actor);
        if ($write) {
            abort_unless(app(ModuleGate::class)->writable('fees', locked: true), 403);
        }
        $year = SessionYear::where('default', 1)->lockForUpdate()->firstOrFail();
        app(FeePlans::class)->year($year);
        abort_unless($yearId === null || (int) $year->id === $yearId, 409, 'The current school year changed. Reopen optional items.');
        $account = FeeAccount::where('active', true)->lockForUpdate()->findOrFail($accountId);
        abort_unless(FeeAccountHolder::where('fee_account_id', $accountId)->where('session_year_id', $year->id)->where('user_id', $actor->id)->lockForUpdate()->exists(), 404);
        $ids = FeeAccountLearner::where('fee_account_id', $accountId)->where('session_year_id', $year->id)->orderBy('learner_id')->lockForUpdate()->pluck('learner_id')->map(fn ($id) => (int) $id)->all();
        $links = GuardianLink::where('guardian_user_id', $actor->id)->whereIn('learner_id', $ids)->where('status', 'active')->where('can_see_fees', true)->orderBy('learner_id')->lockForUpdate()->get()->keyBy('learner_id');
        $visible = array_values(array_intersect($ids, app(ParentFeePolicy::class)->learners($actor)));
        abort_unless($visible !== [], 404);

        return ['account' => $account, 'year' => $year, 'ids' => $ids, 'visible' => $visible, 'links' => $links, 'date' => now(app(CurrentSchool::class)->school()->timezone ?? 'UTC')->toDateString()];
    }

    public function target(array $scope, FeeItem $item, ?int $learner, bool $write = false): array
    {
        if ($item->scope === 'family') {
            abort_unless($learner === null && count($scope['visible']) === count($scope['ids']), 404);
            $ids = $scope['ids'];
        } else {
            abort_unless($learner !== null && in_array($learner, $scope['visible'], true), 404);
            $ids = [$learner];
        }
        $canApprove = true;
        foreach ($ids as $id) {
            if (! $scope['links']->get($id)?->can_approve) {
                $canApprove = false;
            }
        }
        if ($write) {
            abort_unless($canApprove, 403);
            // No optional fee item is chosen for a leaver (Action Plan 1.21). A family item
            // is the account's, so a leaver on it does not block the other children; switching
            // an item off is never refused (LeaverState::writing).
            if ($item->scope !== 'family') {
                app(LeaverState::class)->guardFamilyWrite((int) $learner);
            }
        }
        $learners = Students::whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get(['id', 'user_id']);
        $people = User::whereIn('id', $learners->pluck('user_id'))->lockForUpdate()->get(['id', 'first_name', 'last_name'])->keyBy('id');
        $names = [];
        foreach ($learners as $row) {
            $person = $people->get($row->user_id);
            abort_unless($person !== null, 404);
            $names[] = ['id' => (int) $row->id, 'name' => trim($person->first_name.' '.$person->last_name)];
        }
        abort_unless(count($names) === count($ids), 404);

        return ['ids' => $ids, 'can_approve' => $canApprove, 'learners' => $names, 'target_id' => $item->scope === 'family' ? (int) $scope['account']->id : $learner, 'learner_id' => $learner];
    }

    public function mutation(User $actor, int $accountId, int $itemId, array $data): array
    {
        $scope = $this->account($actor, $accountId, (int) $data['session_year_id'], true);
        $item = FeeItem::lockForUpdate()->findOrFail($itemId);
        $target = $this->target($scope, $item, isset($data['learner_id']) ? (int) $data['learner_id'] : null, true);

        return $scope + ['item' => $item, 'target' => $target];
    }
}
