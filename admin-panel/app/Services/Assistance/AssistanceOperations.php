<?php

declare(strict_types=1);

namespace App\Services\Assistance;

use App\Models\Assistance\AssistanceOperation;
use App\Models\Platform\School;
use App\Models\User;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AssistanceOperations
{
    private function canonical(array $input): array
    {
        if (! array_is_list($input)) {
            ksort($input);
        }

        return array_map(fn ($value) => is_array($value) ? $this->canonical($value) : $value, $input);
    }

    public function once(User $actor, string $kind, array $input, callable $authorize, callable $write): array
    {
        $uuid = Validator::make($input, ['operation_id' => ['required', 'uuid']])->validate()['operation_id'];
        $authorize();
        $school = app(CurrentSchool::class)->id();
        $hash = hash('sha256', json_encode([$kind, $this->canonical($input)], JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($actor, $kind, $uuid, $school, $hash, $authorize, $write): array {
            School::lockForUpdate()->findOrFail($school);
            $authorize();
            abort_unless(app(CurrentSchool::class)->id() === $school, 403);
            $old = AssistanceOperation::where('user_id', $actor->id)->where('operation_id', $uuid)->first();
            if ($old !== null) {
                abort_unless($old->kind === $kind && hash_equals($old->request_hash, $hash), 409, 'This UUID belongs to a different school-support operation.');

                return $old->response;
            }
            $response = $write();
            $authorize();
            abort_unless(app(CurrentSchool::class)->id() === $school, 403);
            AssistanceOperation::create(['id' => (string) Str::uuid(), 'user_id' => $actor->id,
                'operation_id' => $uuid, 'kind' => $kind, 'request_hash' => $hash, 'response' => $response]);

            return $response;
        }, 3);
    }
}
