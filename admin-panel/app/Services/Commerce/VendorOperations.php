<?php

declare(strict_types=1);

namespace App\Services\Commerce;

use App\Models\Commerce\VendorOperation;
use App\Models\Platform\School;
use App\Models\User;
use App\Policies\Commerce\VendorPolicy;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;

/** Independent of wallet availability; never records money or private receipt data. */
final class VendorOperations
{
    public function once(User $actor, string $kind, array $data, callable $write, ?callable $authorize = null): array
    {
        $authorize ??= fn () => app(VendorPolicy::class)->finance($actor, true);
        $school = app(CurrentSchool::class)->id();
        $hash = hash_hmac('sha256', json_encode([$kind, $data], JSON_THROW_ON_ERROR), (string) config('app.key'));

        return DB::transaction(function () use ($actor, $data, $write, $school, $hash, $authorize): array {
            School::whereKey($school)->lockForUpdate()->firstOrFail();
            $authorize();
            $prior = VendorOperation::where('operation_id', $data['operation_id'])->lockForUpdate()->first();
            if ($prior !== null) {
                abort_unless((int) $prior->actor_id === (int) $actor->id && hash_equals($prior->request_hash, $hash), 409, 'This vendor operation already records another change.');

                return $prior->receipt;
            }
            $receipt = $write();
            $authorize();
            abort_unless(app(CurrentSchool::class)->id() === $school, 403);
            VendorOperation::create(['actor_id' => $actor->id, 'operation_id' => $data['operation_id'], 'request_hash' => $hash, 'receipt' => $receipt, 'created_at' => now('UTC')]);

            return $receipt;
        }, 3);
    }
}
