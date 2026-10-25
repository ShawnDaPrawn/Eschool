<?php

declare(strict_types=1);

namespace App\Services\Transfers;

use Illuminate\Support\Facades\DB;

/** Lock only the identities and institution grants of this authorized hand-off. */
class TransferLocks
{
    public function authority(array $schools, array $users): void
    {
        $schools = array_values(array_unique(array_filter($schools)));
        sort($schools);
        $users = array_values(array_unique(array_filter($users)));
        sort($users);
        // Users are global identities. No identity fields are read through this
        // narrow lock; authorized models are resolved in their own school below.
        DB::table('users')->whereIn('id', $users)->orderBy('id')->lockForUpdate()->get(['id']);
        foreach (['school_memberships', 'academic_leaderships', 'guardian_links', 'school_modules'] as $table) {
            $q = DB::table($table)->whereIn('school_id', $schools);
            if ($table === 'school_modules') {
                $q->where('module', 'school_transfers');
            } elseif ($table === 'guardian_links') {
                $q->whereIn('guardian_user_id', $users);
            } else {
                $q->whereIn('user_id', $users);
            }
            $q->orderBy('id')->lockForUpdate()->get(['id']);
        }
        DB::table('platform_staff_identities')->whereIn('user_id', $users)->orderBy('id')->lockForUpdate()->get(['id']);
        $roles = DB::table('roles')->whereIn('school_id', $schools)->whereIn('name', ['Admin', 'Super Admin'])->orderBy('id')->lockForUpdate()->pluck('id')->all();
        if ($roles !== []) {
            DB::table('role_has_permissions')->whereIn('role_id', $roles)->orderBy('role_id')->orderBy('permission_id')->lockForUpdate()->get(['role_id', 'permission_id']);
        }
    }
}
