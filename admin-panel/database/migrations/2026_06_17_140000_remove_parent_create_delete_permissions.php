<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    // parents-create and parents-delete are not used — parents are created/deleted
    // automatically via student records. Only list and edit are valid actions.
    public function up(): void
    {
        $ids = DB::table('permissions')
            ->whereIn('name', ['parents-create', 'parents-delete'])
            ->where('guard_name', 'web')
            ->pluck('id');

        if ($ids->isNotEmpty()) {
            DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
            DB::table('model_has_permissions')->whereIn('permission_id', $ids)->delete();
            DB::table('permissions')->whereIn('id', $ids)->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $now = Carbon::now();

        foreach (['parents-create', 'parents-delete'] as $permName) {
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
