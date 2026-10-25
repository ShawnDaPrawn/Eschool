<?php

declare(strict_types=1);

namespace App\Services\Learning\Digests;

use App\Models\Learning\DigestOperation;
use App\Models\Platform\School;
use App\Models\User;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DigestOperations
{
    public function run(User $actor, string $kind, array $data, callable $authorize, callable $change): array
    {
        Validator::make($data, ['operation_id' => ['required', 'uuid']])->validate();
        $authorize();
        $hash = hash_hmac('sha256', json_encode([$kind, $data], JSON_THROW_ON_ERROR), (string) config('app.key'));

        return DB::transaction(function () use ($actor, $kind, $data, $authorize, $change, $hash): array {
            School::lockForUpdate()->findOrFail(app(CurrentSchool::class)->id());
            $authorize();
            $old = DigestOperation::where('actor_id', $actor->id)->where('operation_id', $data['operation_id'])->lockForUpdate()->first();
            if ($old !== null) {
                abort_unless($old->kind === $kind && hash_equals($old->intent_hash, $hash), 409, 'This weekly update change has different contents.');

                return $old->result;
            }
            $result = $change();
            $authorize();
            DigestOperation::create(['actor_id' => $actor->id, 'operation_id' => $data['operation_id'], 'kind' => $kind, 'intent_hash' => $hash, 'result' => $result]);

            return $result;
        }, 3);
    }
}
