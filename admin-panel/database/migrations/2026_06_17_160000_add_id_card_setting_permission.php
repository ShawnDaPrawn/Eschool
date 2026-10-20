<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = Carbon::now();

        $permissionId = DB::table('permissions')
            ->where('name', 'id-card-setting')
            ->where('guard_name', 'web')
            ->value('id');

        if (! $permissionId) {
            $permissionId = DB::table('permissions')->insertGetId([
                'name'       => 'id-card-setting',
                'guard_name' => 'web',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Assign to Super Admin role
        $roleId = DB::table('roles')->where('name', 'Super Admin')->value('id');

        if ($roleId) {
            $exists = DB::table('role_has_permissions')
                ->where('role_id', $roleId)
                ->where('permission_id', $permissionId)
                ->exists();

            if (! $exists) {
                DB::table('role_has_permissions')->insert([
                    'role_id'       => $roleId,
                    'permission_id' => $permissionId,
                ]);
            }
        }
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')
            ->where('name', 'id-card-setting')
            ->where('guard_name', 'web')
            ->value('id');

        if ($permissionId) {
            $roleId = DB::table('roles')->where('name', 'Super Admin')->value('id');

            if ($roleId) {
                DB::table('role_has_permissions')
                    ->where('permission_id', $permissionId)
                    ->where('role_id', $roleId)
                    ->delete();
            }

            DB::table('permissions')->where('id', $permissionId)->delete();
        }
    }
};
