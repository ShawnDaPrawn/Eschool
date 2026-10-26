<?php

declare(strict_types=1);

namespace App\Services\FamilyVisits;

use App\Models\FamilyVisits\FamilyVisitEvent;
use App\Models\FamilyVisits\FamilyVisitOperation;
use App\Models\Platform\School;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class VisitOperations
{
    /** Laravel validates HTML integers without converting their transport strings. */
    public static function input(array $input, array $rules): array
    {
        $data = Validator::make($input, $rules)->validate();
        foreach ($rules as $key => $fieldRules) {
            if (array_key_exists($key, $data) && in_array('integer', $fieldRules, true)) {
                $data[$key] = (int) $data[$key];
            }
            if (array_key_exists($key, $data) && in_array('accepted', $fieldRules, true)) {
                $data[$key] = true;
            }
        }

        return $data;
    }

    public function once(string $actorType, string $actorId, string $kind, array $input, callable $authorize, callable $write): array
    {
        $operation = Validator::make($input, ['operation_id' => ['required', 'uuid']])->validate()['operation_id'];
        $hash = hash('sha256', json_encode([$kind, $input], JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($actorType, $actorId, $kind, $operation, $hash, $authorize, $write) {
            // Appointment collisions and two readers collecting one pass serialize here.
            School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
            $authorize();
            $old = FamilyVisitOperation::where('actor_type', $actorType)->where('actor_id', $actorId)->where('operation_id', $operation)->first();
            if ($old !== null) {
                abort_unless($old->kind === $kind && hash_equals($old->request_hash, $hash), 409, 'This operation already contains another visit change.');

                return $old->receipt;
            }
            $result = $write();
            $authorize();
            FamilyVisitOperation::create(['actor_type' => $actorType, 'actor_id' => $actorId, 'operation_id' => $operation, 'kind' => $kind, 'request_hash' => $hash, 'receipt' => $result]);
            FamilyVisitEvent::create(['actor_type' => $actorType, 'actor_id' => $actorId, 'kind' => $kind, 'source_id' => $result['id'], 'snapshot' => $result]);

            return $result;
        }, 3);
    }
}
