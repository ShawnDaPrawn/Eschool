<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\Learning\PracticeOperation;
use App\Models\User;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/** Re-authorize immutable source inputs even on replay; never return a stale receipt first. */
class PracticeOperations
{
    public function run(User $actor, array $input, callable $authorize, callable $write): array
    {
        Validator::make($input, ['operation_id' => ['required', 'uuid']])->validate();
        $school = app(CurrentSchool::class)->id();
        $hash = hash('sha256', json_encode($input, JSON_THROW_ON_ERROR));
        $authorize();

        return DB::transaction(function () use ($actor, $input, $authorize, $write, $school, $hash) {
            User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
            $authorize();
            abort_unless(app(CurrentSchool::class)->id() === $school, 403);
            $old = PracticeOperation::where('actor_id', $actor->id)->where('operation_id', $input['operation_id'])->first();
            if ($old !== null) {
                abort_unless(hash_equals($old->request_hash, $hash), 409, 'This practice operation has different content.');
                $authorize();

                return $old->result;
            }
            $result = $write();
            $authorize();
            abort_unless(app(CurrentSchool::class)->id() === $school, 403);
            PracticeOperation::create(['actor_id' => $actor->id, 'operation_id' => $input['operation_id'], 'request_hash' => $hash, 'result' => $result]);

            return $result;
        });
    }
}
