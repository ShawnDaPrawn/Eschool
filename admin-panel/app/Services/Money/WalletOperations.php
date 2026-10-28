<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Models\Money\WalletOperation;
use App\Models\Platform\School;
use App\Models\User;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;

/** Permanent actor-bound receipts; the supplied fresh policy also runs before replay. */
final class WalletOperations
{
    public function once(User $actor, string $kind, array $data, callable $authorize, callable $write): array
    {
        $school = app(CurrentSchool::class)->id();
        $hash = hash_hmac('sha256', json_encode([$kind, $data], JSON_THROW_ON_ERROR), (string) config('app.key'));

        return DB::transaction(function () use ($actor, $data, $authorize, $write, $school, $hash): array {
            School::whereKey($school)->lockForUpdate()->firstOrFail();
            $authorize();
            $prior = WalletOperation::where('operation_id', $data['operation_id'])->lockForUpdate()->first();
            if ($prior !== null) {
                abort_unless((int) $prior->actor_id === (int) $actor->id && hash_equals($prior->request_hash, $hash), 409, 'This wallet operation already records another change.');

                return $prior->receipt;
            }
            $receipt = $write();
            $authorize();
            abort_unless(app(CurrentSchool::class)->id() === $school, 403);
            WalletOperation::create(['actor_id' => $actor->id, 'operation_id' => $data['operation_id'], 'request_hash' => $hash, 'receipt' => $receipt, 'created_at' => now('UTC')]);

            return $receipt;
        }, 3);
    }
}
