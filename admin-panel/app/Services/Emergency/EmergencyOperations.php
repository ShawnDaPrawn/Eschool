<?php

declare(strict_types=1);

namespace App\Services\Emergency;

use App\Models\Emergency\EmergencyEvent;
use App\Models\Emergency\EmergencyOperation;
use App\Models\Platform\School;
use App\Models\User;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Every emergency write runs once per operation id: a retry with the same id
 * and body returns the first receipt (after authority is checked again), and
 * never sends an alert or releases a child twice. Writes serialize on the
 * school, so two people cannot confirm two alerts or release one child twice.
 */
class EmergencyOperations
{
    /** Validate, then make integers and accepted checkboxes their real types. */
    public static function input(array $input, array $rules): array
    {
        $data = Validator::make($input, $rules)->validate();
        foreach ($rules as $key => $fieldRules) {
            if (! array_key_exists($key, $data) || ! is_array($fieldRules)) {
                continue;
            }
            if (in_array('integer', $fieldRules, true) && $data[$key] !== null) {
                $data[$key] = (int) $data[$key];
            }
            if (in_array('accepted', $fieldRules, true) || in_array('boolean', $fieldRules, true)) {
                $data[$key] = filter_var($data[$key], FILTER_VALIDATE_BOOLEAN);
            }
        }

        return $data;
    }

    public function once(User $actor, string $kind, array $input, callable $authorize, callable $write): array
    {
        $operation = Validator::make($input, ['operation_id' => ['required', 'uuid']])->validate()['operation_id'];
        $hash = hash('sha256', json_encode([$kind, $input], JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($actor, $kind, $operation, $hash, $authorize, $write) {
            School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
            $authorize();
            $old = EmergencyOperation::where('actor_id', $actor->id)->where('operation_id', $operation)->first();
            if ($old !== null) {
                abort_unless($old->kind === $kind && hash_equals($old->request_hash, $hash), 409, 'This operation already holds another emergency change.');

                return $old->receipt;
            }
            $result = $write();
            EmergencyOperation::create(['actor_id' => $actor->id, 'operation_id' => $operation, 'kind' => $kind, 'request_hash' => $hash, 'receipt' => $result]);

            return $result;
        }, 3);
    }

    /** One line in the audit trail. Details hold ids and states only, never a visitor's or collector's details. */
    public static function event(User $actor, string $kind, ?string $alertId, ?string $subjectId, array $details = []): void
    {
        EmergencyEvent::create(['actor_id' => $actor->id, 'kind' => $kind, 'alert_id' => $alertId, 'subject_id' => $subjectId, 'details' => $details === [] ? null : $details, 'created_at' => now()->utc()]);
    }
}
