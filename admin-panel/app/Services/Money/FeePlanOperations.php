<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Models\Money\FeePlanOperation;
use App\Models\Platform\School;
use App\Models\User;
use App\Policies\Money\FeePlanPolicy;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;

final class FeePlanOperations
{
    /** @return array{id: int, revision: int} */
    public function once(User $actor, string $kind, array $data, callable $write): array
    {
        app(FeePlanPolicy::class)->authorize($actor);
        $school = app(CurrentSchool::class)->id();
        $hash = hash_hmac('sha256', json_encode([$kind, $data], JSON_THROW_ON_ERROR), (string) config('app.key'));

        return DB::transaction(function () use ($actor, $data, $write, $school, $hash): array {
            School::whereKey($school)->lockForUpdate()->firstOrFail();
            app(FeePlanPolicy::class)->authorize($actor, true);
            $prior = FeePlanOperation::where('operation_id', $data['operation_id'])->lockForUpdate()->first();
            if ($prior !== null) {
                abort_unless((int) $prior->actor_id === (int) $actor->id && hash_equals($prior->request_hash, $hash), 409, 'This operation reference already has a different change.');

                return $prior->receipt;
            }
            $receipt = $write();
            app(FeePlanPolicy::class)->authorize($actor, true);
            abort_unless(app(CurrentSchool::class)->id() === $school, 403);
            FeePlanOperation::create(['actor_id' => $actor->id, 'operation_id' => $data['operation_id'], 'request_hash' => $hash, 'receipt' => $receipt]);

            return $receipt;
        }, 3);
    }
}
