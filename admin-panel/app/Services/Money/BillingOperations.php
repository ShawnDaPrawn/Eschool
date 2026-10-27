<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Models\Money\BillingOperation;
use App\Models\Platform\School;
use App\Models\User;
use App\Policies\Money\MoneyPolicy;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;

final class BillingOperations
{
    public function once(User $actor, string $permission, string $kind, array $data, callable $write): array
    {
        $policy = app(MoneyPolicy::class);
        $policy->authorize($actor, $permission);
        $school = app(CurrentSchool::class)->id();
        $hash = hash_hmac('sha256', json_encode([$permission, $kind, $data], JSON_THROW_ON_ERROR), (string) config('app.key'));

        return DB::transaction(function () use ($actor, $permission, $data, $write, $school, $hash, $policy): array {
            School::whereKey($school)->lockForUpdate()->firstOrFail();
            $policy->authorize($actor, $permission, true);
            $prior = BillingOperation::where('operation_id', $data['operation_id'])->lockForUpdate()->first();
            if ($prior !== null) {
                abort_unless((int) $prior->actor_id === (int) $actor->id && hash_equals($prior->request_hash, $hash), 409, 'This operation reference already has a different billing change.');

                return $prior->receipt;
            }
            $receipt = $write();
            $policy->authorize($actor, $permission, true);
            abort_unless(app(CurrentSchool::class)->id() === $school, 403);
            BillingOperation::create(['actor_id' => $actor->id, 'operation_id' => $data['operation_id'], 'request_hash' => $hash, 'receipt' => $receipt]);

            return $receipt;
        }, 3);
    }
}
