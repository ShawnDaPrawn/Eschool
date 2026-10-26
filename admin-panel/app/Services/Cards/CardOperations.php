<?php

declare(strict_types=1);

namespace App\Services\Cards;

use App\Models\Cards\CardOperation;
use App\Models\Platform\School;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CardOperations
{
    /** Every replay reauthorizes before decrypting its bounded private receipt. */
    public function once(string $actorKind, string $actorRef, string $kind, array $input, callable $authority, callable $write): array
    {
        abort_unless(in_array($actorKind, ['user', 'device'], true) && strlen($actorRef) <= 40, 422);
        $operation = Validator::make($input, ['operation_id' => ['required', 'uuid']])->validate()['operation_id'];
        $hash = hash('sha256', json_encode([$kind, $input], JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($actorKind, $actorRef, $kind, $operation, $hash, $authority, $write) {
            School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
            $authority();
            $old = CardOperation::where('actor_kind', $actorKind)->where('actor_ref', $actorRef)->where('operation_id', $operation)->first();
            if ($old !== null) {
                abort_unless(hash_equals($old->request_hash, $hash) && $old->kind === $kind, 409, 'This card operation already contains another change.');

                return $old->receipt;
            }
            $result = $write();
            CardOperation::create(['actor_kind' => $actorKind, 'actor_ref' => $actorRef, 'operation_id' => $operation, 'kind' => $kind, 'request_hash' => $hash, 'receipt' => $result]);

            return $result;
        }, 3);
    }
}
