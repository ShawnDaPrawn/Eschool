<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\Learning\ExamCoachOperation;
use App\Models\Platform\School;
use App\Models\User;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ExamCoachOperations
{
    public function run(User $actor, string $kind, array $input, callable $authorize, callable $write): array
    {
        Validator::make($input, ['operation_id' => ['required', 'uuid']])->validate();
        $school = app(CurrentSchool::class)->id();
        $hash = hash_hmac('sha256', json_encode([$kind, $input], JSON_THROW_ON_ERROR), config('app.key'));
        $guard = function () use ($school, $authorize) {
            abort_unless(app(CurrentSchool::class)->id() === $school, 403);
            $authorize();
            abort_unless(app(CurrentSchool::class)->id() === $school, 403);
        };
        $guard();

        return DB::transaction(function () use ($actor, $input, $write, $school, $hash, $guard) {
            School::lockForUpdate()->findOrFail($school);
            $guard();
            $old = ExamCoachOperation::where('actor_id', $actor->id)->where('operation_id', $input['operation_id'])->first();
            if ($old !== null) {
                abort_unless(hash_equals($old->request_hash, $hash), 409, 'This exam-coach request changed.');
                $guard();

                return $old->result;
            }
            $result = $write();
            $guard();
            ExamCoachOperation::create(['actor_id' => $actor->id, 'operation_id' => $input['operation_id'], 'request_hash' => $hash, 'result' => $result]);

            return $result;
        });
    }
}
