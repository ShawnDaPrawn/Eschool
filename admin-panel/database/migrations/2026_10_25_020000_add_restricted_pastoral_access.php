<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_staff_identities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->restrictOnDelete();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
        foreach (['pastoral-sensitive-access', 'activity-groups-manage'] as $name) {
            DB::table('permissions')->insertOrIgnore(['name' => $name, 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()]);
        }
        foreach (DB::table('schools')->pluck('id') as $schoolId) {
            if (! DB::table('roles')->where('school_id', $schoolId)->exists()) {
                // A fresh install creates the original roles first through InstallationSeeder.
                continue;
            }
            foreach (['Facilitator', 'Counsellor', 'Safeguarding'] as $role) {
                DB::table('roles')->insertOrIgnore(['name' => $role, 'school_id' => $schoolId, 'guard_name' => 'web', 'custom_role' => 0, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
        foreach (['pastoral-sensitive-access' => ['Counsellor', 'Safeguarding'], 'activity-groups-manage' => ['Super Admin']] as $permission => $roles) {
            $permissionId = DB::table('permissions')->where('name', $permission)->where('guard_name', 'web')->value('id');
            foreach (DB::table('roles')->whereIn('name', $roles)->where(fn ($q) => $q->whereNull('custom_role')->orWhere('custom_role', 0))->pluck('id') as $roleId) {
                DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $permissionId, 'role_id' => $roleId]);
            }
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        if (DB::table('platform_staff_identities')->exists()) {
            throw new RuntimeException('Platform staff exclusions are retained; rollback refused.');
        }
        Schema::dropIfExists('platform_staff_identities');
        // Keep roles/permissions: later domain records or custom grants may reference them.
    }
};
