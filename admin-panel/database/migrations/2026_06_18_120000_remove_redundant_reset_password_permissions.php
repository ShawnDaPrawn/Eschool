<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    // The student password-reset feature was gated by three permissions for a
    // single workflow (student-reset-password = menu/list, reset-password-list =
    // list data, student-change-password = the reset action). Consolidate to the
    // single student-reset-password permission and drop the other two. Any role
    // or user that held a removed permission inherits student-reset-password so
    // existing access is preserved.
    public function up(): void
    {
        $removed = ['reset-password-list', 'student-change-password'];

        $removedIds = DB::table('permissions')
            ->whereIn('name', $removed)
            ->where('guard_name', 'web')
            ->pluck('id');

        if ($removedIds->isEmpty()) {
            return;
        }

        $keepId = DB::table('permissions')
            ->where('name', 'student-reset-password')
            ->where('guard_name', 'web')
            ->value('id');

        // Preserve access: grant student-reset-password to any role/user that
        // held a removed permission but not the kept one (e.g. custom roles).
        if ($keepId) {
            $roleIds = DB::table('role_has_permissions')
                ->whereIn('permission_id', $removedIds)
                ->where('role_id', '!=', $keepId)
                ->distinct()
                ->pluck('role_id');

            foreach ($roleIds as $roleId) {
                $hasKeep = DB::table('role_has_permissions')
                    ->where('permission_id', $keepId)
                    ->where('role_id', $roleId)
                    ->exists();

                if (! $hasKeep) {
                    DB::table('role_has_permissions')->insert([
                        'permission_id' => $keepId,
                        'role_id' => $roleId,
                    ]);
                }
            }

            $modelRows = DB::table('model_has_permissions')
                ->whereIn('permission_id', $removedIds)
                ->get(['model_type', 'model_id']);

            foreach ($modelRows as $row) {
                $hasKeep = DB::table('model_has_permissions')
                    ->where('permission_id', $keepId)
                    ->where('model_type', $row->model_type)
                    ->where('model_id', $row->model_id)
                    ->exists();

                if (! $hasKeep) {
                    DB::table('model_has_permissions')->insert([
                        'permission_id' => $keepId,
                        'model_type' => $row->model_type,
                        'model_id' => $row->model_id,
                    ]);
                }
            }
        }

        // Remove the redundant permissions and their pivot rows.
        DB::table('role_has_permissions')->whereIn('permission_id', $removedIds)->delete();
        DB::table('model_has_permissions')->whereIn('permission_id', $removedIds)->delete();
        DB::table('permissions')->whereIn('id', $removedIds)->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $now = Carbon::now();

        foreach (['reset-password-list', 'student-change-password'] as $permName) {
            $exists = DB::table('permissions')
                ->where('name', $permName)
                ->where('guard_name', 'web')
                ->exists();

            if (! $exists) {
                $permissionId = DB::table('permissions')->insertGetId([
                    'name' => $permName,
                    'guard_name' => 'web',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                if (DB::table('roles')->where('id', 1)->exists()) {
                    DB::table('role_has_permissions')->insert([
                        'role_id' => 1,
                        'permission_id' => $permissionId,
                    ]);
                }
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
