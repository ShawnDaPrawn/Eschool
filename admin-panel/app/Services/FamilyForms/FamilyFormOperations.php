<?php

declare(strict_types=1);

namespace App\Services\FamilyForms;

use App\Models\FamilyForms\FamilyFormOperation;
use App\Models\Platform\School;
use App\Models\User;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class FamilyFormOperations
{
    public function once(User $actor, string $kind, array $input, callable $authorize, callable $write): array
    {
        $operation = Validator::make($input, ['operation_id' => ['required', 'uuid']])->validate()['operation_id'];
        $hash = hash('sha256', json_encode([$kind, $input], JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($actor, $kind, $operation, $hash, $authorize, $write) {
            School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
            $authorize();
            $old = FamilyFormOperation::where('actor_id', $actor->id)->where('operation_id', $operation)->first();
            if ($old !== null) {
                abort_unless($old->kind === $kind && hash_equals($old->request_hash, $hash), 409, 'This operation already contains another family change.');

                return $old->receipt;
            }
            $result = $write();
            $authorize();
            FamilyFormOperation::create(['actor_id' => $actor->id, 'operation_id' => $operation, 'kind' => $kind, 'request_hash' => $hash, 'receipt' => $result]);

            return $result;
        }, 3);
    }
}
