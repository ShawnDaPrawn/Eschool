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
        Schema::create('ai_school_limits', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->unique()->constrained('schools')->restrictOnDelete();
            $t->unsignedInteger('revision')->default(1);
            $t->boolean('paused')->default(false);
            $t->json('limits');
            $t->timestamps();
        });
        Schema::create('ai_settings_operations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $t->uuid('operation_id');
            $t->string('intent_hash', 64);
            $t->unsignedInteger('revision');
            $t->timestamps();
            $t->unique(['school_id', 'user_id', 'operation_id'], 'ai_settings_operation');
        });
        Schema::create('platform_ai_cost_grants', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->unique()->constrained('users')->restrictOnDelete();
            $t->boolean('active')->default(false);
            $t->timestamp('expires_at');
            $t->timestamps();
        });
        Schema::table('ai_usage', fn (Blueprint $t) => $t->char('billing_currency', 3)->nullable());
        DB::table('permissions')->insertOrIgnore(['name' => 'ai-settings-manage', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()]);
        $id = DB::table('permissions')->where('name', 'ai-settings-manage')->value('id');
        foreach (DB::table('roles')->whereIn('name', ['Super Admin', 'School Admin', 'Principal'])->where(fn ($q) => $q->whereNull('custom_role')->orWhere('custom_role', 0))->pluck('id') as $role) {
            DB::table('role_has_permissions')->insertOrIgnore(['role_id' => $role, 'permission_id' => $id]);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        if (DB::table('ai_settings_operations')->exists() || DB::table('platform_ai_cost_grants')->exists()) {
            throw new RuntimeException('Retain AI control and grant history.');
        }
        Schema::dropIfExists('platform_ai_cost_grants');
        Schema::dropIfExists('ai_settings_operations');
        Schema::dropIfExists('ai_school_limits');
        Schema::table('ai_usage', fn (Blueprint $t) => $t->dropColumn('billing_currency'));
    }
};
