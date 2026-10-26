<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\Learning\TeachingDraftOperation;
use App\Models\Platform\School;
use App\Models\User;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TeachingDraftOperations
{
    public function run(User $actor, array $input, callable $authorize, callable $write): array
    {
        Validator::make($input, ['operation_id' => ['required', 'uuid']])->validate();
        $school = app(CurrentSchool::class)->id();
        $hash = hash_hmac('sha256', json_encode($input, JSON_THROW_ON_ERROR), config('app.key'));
        $authorize();

        return DB::transaction(function () use ($actor, $input, $authorize, $write, $school, $hash) {
            School::lockForUpdate()->findOrFail($school);
            $authorize();
            abort_unless(app(CurrentSchool::class)->id() === $school, 403);
            $old = TeachingDraftOperation::where('actor_id', $actor->id)->where('operation_id', $input['operation_id'])->first();
            if ($old !== null) {
                abort_unless(hash_equals($old->request_hash, $hash), 409, 'This teaching operation has different content.');
                $authorize();

                return $old->result;
            }
            $result = $write();
            $authorize();
            abort_unless(app(CurrentSchool::class)->id() === $school, 403);
            TeachingDraftOperation::create(['actor_id' => $actor->id, 'operation_id' => $input['operation_id'], 'request_hash' => $hash, 'result' => $result]);

            return $result;
        }, 3);
    }
}
