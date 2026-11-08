<?php

declare(strict_types=1);

namespace App\Services\People\Pools;

use App\Models\People\Pools\PoolOperation;
use App\Models\Platform\School;
use App\Models\User;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Idempotent pool writes. The operation id and a hash of the request are
 * persisted with a receipt that holds record ids only (never a name, an
 * email address or a password), so a replay re-reads the current record
 * and nothing personal outlives the record itself. Authority is checked
 * again on every replay, after the school lock.
 */
final class PoolOperations
{
    /**
     * @param  callable(): mixed  $authority
     * @param  callable(): array<string, int>  $write
     * @return array<string, int>
     */
    public function once(User $actor, string $kind, array $data, callable $authority, callable $write): array
    {
        $uuid = Validator::make($data, ['operation_id' => ['required', 'uuid']])->validate()['operation_id'];
        $hash = hash('sha256', json_encode([$kind, $data], JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($actor, $authority, $write, $uuid, $hash) {
            School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
            $authority();
            $old = PoolOperation::where('actor_id', $actor->id)->where('operation_id', $uuid)->lockForUpdate()->first();
            if ($old !== null) {
                abort_unless(hash_equals($old->request_hash, $hash), 409, 'This operation already contains a different pool change.');

                return $old->receipt;
            }
            $receipt = $write();
            PoolOperation::create(['actor_id' => $actor->id, 'operation_id' => $uuid, 'request_hash' => $hash, 'receipt' => $receipt]);

            return $receipt;
        }, 3);
    }
}
