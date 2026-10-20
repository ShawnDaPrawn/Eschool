<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * The "chat-message-delete" permission is merged into "chat-settings" — the chat
     * settings page is the only screen it gated, so "chat-settings" now covers both
     * viewing the page and deleting chat messages. Drop the now-unused permission.
     */
    public function up(): void
    {
        $permissionId = DB::table('permissions')
            ->where('name', 'chat-message-delete')
            ->where('guard_name', 'web')
            ->value('id');

        if ($permissionId) {
            // Detach from any role / directly-assigned model, then delete the permission.
            DB::table('role_has_permissions')->where('permission_id', $permissionId)->delete();
            DB::table('model_has_permissions')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $now = Carbon::now();

        $permissionId = DB::table('permissions')
            ->where('name', 'chat-message-delete')
            ->where('guard_name', 'web')
            ->value('id');

        if (! $permissionId) {
            $permissionId = DB::table('permissions')->insertGetId([
                'name' => 'chat-message-delete',
                'guard_name' => 'web',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Restore to the Super Admin role (role id 1), mirroring the installation seeder.
        if (DB::table('roles')->where('id', 1)->exists()) {
            $exists = DB::table('role_has_permissions')
                ->where('role_id', 1)
                ->where('permission_id', $permissionId)
                ->exists();

            if (! $exists) {
                DB::table('role_has_permissions')->insert([
                    'role_id' => 1,
                    'permission_id' => $permissionId,
                ]);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
