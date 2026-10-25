<?php

declare(strict_types=1);

namespace App\Services\Activities;

use App\Models\Activities\ActivityOperation;
use App\Models\Platform\School;
use App\Models\User;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ActivityOperations
{
    public function once(User $actor, string $kind, array $data, callable $authority, callable $write): array
    {
        $uuid = Validator::make($data, ['operation_id' => ['required', 'uuid']])->validate()['operation_id'];
        $hash = hash('sha256', json_encode([$kind, $data], JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($actor, $authority, $write, $uuid, $hash) {
            School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
            $authority();
            $old = ActivityOperation::where('actor_id', $actor->id)->where('operation_id', $uuid)->first();
            if ($old !== null) {
                abort_unless(hash_equals($old->request_hash, $hash), 409, 'This operation already contains a different activity change.');

                return $old->receipt;
            }
            $result = $write();
            ActivityOperation::create(['actor_id' => $actor->id, 'operation_id' => $uuid, 'request_hash' => $hash, 'receipt' => $result]);

            return $result;
        }, 3);
    }
}
