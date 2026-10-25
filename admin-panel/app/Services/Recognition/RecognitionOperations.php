<?php

declare(strict_types=1);

namespace App\Services\Recognition;

use App\Models\Platform\School;
use App\Models\Recognition\RecognitionOperation;
use App\Models\User;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RecognitionOperations
{
    /** Fresh locking reads precede a transaction's normal authorization snapshot. */
    public function lock(User $actor): void
    {
        $school = app(CurrentSchool::class)->id();
        School::whereKey($school)->lockForUpdate()->firstOrFail();
        DB::table('users')->where('id', $actor->id)->lockForUpdate()->get(['id']);
        foreach (['school_memberships', 'academic_leaderships', 'activity_group_staff', 'award_category_admins'] as $table) {
            DB::table($table)->where('school_id', $school)->where('user_id', $actor->id)->orderBy('id')->lockForUpdate()->get(['id']);
        }
        DB::table('school_modules')->where('school_id', $school)->whereIn('module', ['house_points', 'awards', 'groups'])->orderBy('id')->lockForUpdate()->get(['id']);
        DB::table('platform_staff_identities')->where('user_id', $actor->id)->lockForUpdate()->get(['id']);
        $roles = DB::table('roles')->where('school_id', $school)->orderBy('id')->lockForUpdate()->pluck('id')->all();
        if ($roles !== []) {
            DB::table('role_has_permissions')->whereIn('role_id', $roles)->orderBy('role_id')->orderBy('permission_id')->lockForUpdate()->get(['role_id', 'permission_id']);
        }
        DB::table('model_has_permissions')->where('school_id', $school)->where('model_id', $actor->id)->lockForUpdate()->get(['permission_id']);
    }

    /** Receipts contain references only, never cached private learner bodies. */
    public function once(User $actor, string $kind, array $intent, callable $authority, callable $write, callable $replay): mixed
    {
        $authority();
        $uuid = Validator::make($intent, ['operation_id' => ['required', 'uuid']])->validate()['operation_id'];
        $hash = hash_hmac('sha256', json_encode([$kind, $intent], JSON_THROW_ON_ERROR), (string) config('app.key'));
        $school = app(CurrentSchool::class)->id();

        return DB::transaction(function () use ($actor, $kind, $uuid, $hash, $school, $authority, $write, $replay) {
            abort_unless(app(CurrentSchool::class)->id() === $school, 403);
            $this->lock($actor);
            $authority();
            $prior = RecognitionOperation::where('actor_id', $actor->id)->where('operation_id', $uuid)->first();
            if ($prior !== null) {
                abort_unless(hash_equals($prior->request_hash, $hash), 409, 'This operation contains a different recognition change.');

                return $replay((int) $prior->record_id);
            }
            $record = $write();
            abort_unless(app(CurrentSchool::class)->id() === $school, 403);
            $authority();
            RecognitionOperation::create(['actor_id' => $actor->id, 'operation_id' => $uuid, 'request_hash' => $hash, 'kind' => $kind, 'record_id' => $record->id, 'revision' => (int) ($record->revision ?? 1)]);

            return $record;
        }, 3);
    }
}
