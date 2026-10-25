<?php

declare(strict_types=1);

namespace App\Services\Admissions;

use App\Models\Admissions\AdmissionOperation;
use App\Models\Platform\School;
use App\Models\User;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdmissionOperations
{
    /** References only, with authority checked both before retry and commit. */
    public function once(User $actor, string $kind, array $input, callable $write): array
    {
        app(IntakeAuthority::class)->staff($actor, true);
        abort_unless(Str::isUuid($input['operation_id'] ?? null), 422);
        $school = app(CurrentSchool::class)->id();
        $hash = hash_hmac('sha256', json_encode([$kind, $input], JSON_THROW_ON_ERROR), (string) config('app.key'));

        return DB::transaction(function () use ($actor, $kind, $input, $write, $school, $hash) {
            // One school mutex also serializes capacity and allocation decisions.
            School::whereKey($school)->lockForUpdate()->firstOrFail();
            DB::table('users')->where('id', $actor->id)->lockForUpdate()->first();
            DB::table('school_memberships')->where('school_id', $school)->where('user_id', $actor->id)->lockForUpdate()->get();
            DB::table('academic_leaderships')->where('school_id', $school)->where('user_id', $actor->id)->orderBy('id')->lockForUpdate()->get(['id']);
            DB::table('school_modules')->where('school_id', $school)->whereIn('module', ['intake', 'people', 'academics', 'house_points'])->orderBy('id')->lockForUpdate()->get(['id']);
            DB::table('platform_staff_identities')->where('user_id', $actor->id)->lockForUpdate()->get(['id']);
            $roles = DB::table('roles')->where('school_id', $school)->orderBy('id')->lockForUpdate()->pluck('id')->all();
            if ($roles !== []) {
                DB::table('role_has_permissions')->whereIn('role_id', $roles)->orderBy('role_id')->orderBy('permission_id')->lockForUpdate()->get(['role_id', 'permission_id']);
            }
            DB::table('model_has_permissions')->where('school_id', $school)->where('model_id', $actor->id)->lockForUpdate()->get(['permission_id']);
            app(IntakeAuthority::class)->staff($actor, true);
            $prior = AdmissionOperation::where('actor_id', $actor->id)->where('operation_id', $input['operation_id'])->first();
            if ($prior !== null) {
                abort_unless(hash_equals($prior->request_hash, $hash), 409, 'Compare the existing admission change before retrying.');

                return $prior->receipt;
            }
            $receipt = $write();
            app(IntakeAuthority::class)->staff($actor, true);
            abort_unless(app(CurrentSchool::class)->id() === $school, 403);
            AdmissionOperation::create(['actor_id' => $actor->id, 'operation_id' => $input['operation_id'], 'kind' => $kind, 'request_hash' => $hash, 'receipt' => $receipt]);

            return $receipt;
        }, 3);
    }
}
