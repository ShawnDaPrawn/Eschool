<?php

declare(strict_types=1);

namespace App\Services\SchoolBusiness;

use App\Models\Platform\School;
use App\Models\SchoolBusiness\BusinessOperation;
use App\Models\User;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Exact retries of a write return the first receipt; a different request
 * under the same operation_id is refused. The operation_id and a keyed hash
 * of the request are stored durably; the receipt holds identifiers only.
 */
class BusinessOperations
{
    public function once(User $actor, string $kind, array $input, callable $authorize, callable $write): array
    {
        $operation = Validator::make($input, ['operation_id' => ['required', 'uuid']])->validate()['operation_id'];
        $hash = hash_hmac('sha256', json_encode([$kind, $input], JSON_THROW_ON_ERROR), (string) config('app.key'));
        $school = app(CurrentSchool::class)->id();

        return DB::transaction(function () use ($actor, $kind, $operation, $hash, $authorize, $write, $school): array {
            School::whereKey($school)->lockForUpdate()->firstOrFail();
            $authorize();
            $old = BusinessOperation::where('actor_id', $actor->id)->where('operation_id', $operation)->lockForUpdate()->first();
            if ($old !== null) {
                abort_unless($old->kind === $kind && hash_equals($old->request_hash, $hash), 409, 'This operation reference already holds a different change.');

                return $old->receipt;
            }
            $receipt = $write();
            $authorize();
            abort_unless(app(CurrentSchool::class)->id() === $school, 403);
            BusinessOperation::create(['actor_id' => $actor->id, 'operation_id' => $operation, 'kind' => $kind, 'request_hash' => $hash, 'receipt' => $receipt, 'created_at' => now('UTC')]);

            return $receipt;
        }, 3);
    }
}
