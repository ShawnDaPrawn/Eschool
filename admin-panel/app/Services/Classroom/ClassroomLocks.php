<?php

declare(strict_types=1);

namespace App\Services\Classroom;

use App\Models\Classroom\ClassroomLease;
use App\Models\User;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;

/** Acquire authority rows before the first consistent-read snapshot. */
class ClassroomLocks
{
    public function staff(int $actor, int $group, ?string $deviceId = null): void
    {
        $school = app(CurrentSchool::class)->id();
        DB::table('schools')->where('id', $school)->lockForUpdate()->firstOrFail();
        $users = [$actor];
        if ($deviceId !== null) {
            $device = DB::table('card_reader_devices')->where('school_id', $school)->where('id', $deviceId)->lockForUpdate()->firstOrFail();
            $users[] = (int) $device->issued_by;
        }
        $users = array_values(array_unique($users));
        sort($users);
        DB::table('users')->whereIn('id', $users)->orderBy('id')->lockForUpdate()->get(['id']);
        DB::table('platform_staff_identities')->whereIn('user_id', $users)->orderBy('id')->lockForUpdate()->get(['id']);
        DB::table('school_memberships')->where('school_id', $school)->whereIn('user_id', $users)->orderBy('id')->lockForUpdate()->get(['id']);
        DB::table('teachers')->where('school_id', $school)->whereIn('user_id', $users)->orderBy('id')->lockForUpdate()->get(['id']);
        $row = DB::table('teaching_groups')->where('school_id', $school)->where('id', $group)->lockForUpdate()->firstOrFail();
        DB::table('session_years')->where('school_id', $school)->where('id', $row->session_year_id)->lockForUpdate()->get(['id']);
        DB::table('settings')->where('school_id', $school)->where('type', 'session_year')->lockForUpdate()->get(['id']);
        DB::table('teaching_allocations')->where('school_id', $school)->where('teaching_group_id', $group)->orderBy('id')->lockForUpdate()->get(['id']);
        DB::table('foundation_classroom_groups')->where('school_id', $school)->where('teaching_group_id', $group)->lockForUpdate()->get(['id']);
        DB::table('school_modules')->where('school_id', $school)->whereIn('module', ['cards', 'daily_practice', 'mastery'])->orderBy('id')->lockForUpdate()->get(['id']);
        $tables = config('permission.table_names');
        $roles = DB::table($tables['roles'])->where('school_id', $school)->orderBy('id')->lockForUpdate()->get(['id'])->pluck('id')->all();
        DB::table($tables['role_has_permissions'])->whereIn('role_id', $roles)->lockForUpdate()->get();
        DB::table($tables['model_has_roles'])->where('model_type', (new User)->getMorphClass())->whereIn('model_id', $users)->lockForUpdate()->get();
        DB::table($tables['model_has_permissions'])->where('model_type', (new User)->getMorphClass())->whereIn('model_id', $users)->lockForUpdate()->get();
    }

    public function acquire(ClassroomLease $lease, string $cardId): void
    {
        $this->staff((int) $lease->teacher_user_id, (int) $lease->teaching_group_id, $lease->device_id);
        $school = app(CurrentSchool::class)->id();
        DB::table('schools')->where('id', $school)->lockForUpdate()->firstOrFail();
        $device = DB::table('card_reader_devices')->where('school_id', $school)->where('id', $lease->device_id)->lockForUpdate()->firstOrFail();
        DB::table('foundation_classroom_leases')->where('school_id', $school)->where('id', $lease->id)->lockForUpdate()->firstOrFail();
        DB::table('foundation_classroom_groups')->where('school_id', $school)->where('teaching_group_id', $lease->teaching_group_id)->lockForUpdate()->get();
        $card = DB::table('cards')->where('school_id', $school)->where('id', $cardId)->lockForUpdate()->firstOrFail();
        $learner = DB::table('students')->where('school_id', $school)->where('user_id', $card->user_id)->lockForUpdate()->firstOrFail();
        $users = array_values(array_unique([(int) $lease->teacher_user_id, (int) $device->issued_by, (int) $card->user_id]));
        sort($users);
        DB::table('users')->whereIn('id', $users)->orderBy('id')->lockForUpdate()->get();
        DB::table('school_memberships')->where('school_id', $school)->whereIn('user_id', $users)->orderBy('id')->lockForUpdate()->get();
        DB::table('teaching_groups')->where('school_id', $school)->where('id', $lease->teaching_group_id)->lockForUpdate()->get();
        $items = DB::table('practice_items')->where('school_id', $school)->where('teaching_group_id', $lease->teaching_group_id)->orderBy('id')->lockForUpdate()->get(['id'])->pluck('id')->all();
        DB::table('practice_item_versions')->where('school_id', $school)->whereIn('practice_item_id', $items)->orderBy('id')->lockForUpdate()->get(['id']);
        foreach (['teaching_allocations', 'teaching_group_memberships'] as $table) {
            DB::table($table)->where('school_id', $school)->where('teaching_group_id', $lease->teaching_group_id)->orderBy('id')->lockForUpdate()->get();
        }
        $guardians = [];
        foreach (['enrolments', 'guardian_links', 'learner_approvals'] as $table) {
            $rows = DB::table($table)->where('school_id', $school)->where('learner_id', $learner->id)->orderBy('id')->lockForUpdate()->get();
            if ($table !== 'enrolments') {
                foreach ($rows as $source) {
                    $guardians[] = (int) $source->guardian_user_id;
                }
            }
        }
        $users = array_values(array_unique(array_merge($users, $guardians)));
        sort($users);
        DB::table('users')->whereIn('id', $users)->orderBy('id')->lockForUpdate()->get(['id']);
        DB::table('school_memberships')->where('school_id', $school)->whereIn('user_id', $users)->orderBy('id')->lockForUpdate()->get(['id']);
        DB::table('platform_staff_identities')->whereIn('user_id', $users)->orderBy('id')->lockForUpdate()->get(['id']);
        DB::table('school_modules')->where('school_id', $school)->whereIn('module', ['cards', 'daily_practice', 'mastery'])->orderBy('id')->lockForUpdate()->get();
        $tables = config('permission.table_names');
        $roles = DB::table($tables['roles'])->where('school_id', $school)->orderBy('id')->lockForUpdate()->get(['id'])->pluck('id')->all();
        DB::table($tables['role_has_permissions'])->whereIn('role_id', $roles)->lockForUpdate()->get();
        DB::table($tables['model_has_roles'])->where('model_type', (new User)->getMorphClass())->whereIn('model_id', $users)->lockForUpdate()->get();
        DB::table($tables['model_has_permissions'])->where('model_type', (new User)->getMorphClass())->whereIn('model_id', $users)->lockForUpdate()->get();
    }
}
