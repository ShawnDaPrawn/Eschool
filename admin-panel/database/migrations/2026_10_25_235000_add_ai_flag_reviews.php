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
        Schema::table('ai_flags', function (Blueprint $t) {
            $t->unsignedInteger('review_revision')->default(1);
            $t->string('review_status', 16)->default('open');
        });
        Schema::create('ai_flag_reviews', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('ai_flag_id')->constrained('ai_flags')->cascadeOnDelete();
            $t->foreignId('reviewed_by')->constrained('users')->restrictOnDelete();
            $t->uuid('operation_id');
            $t->string('intent_hash', 64);
            $t->string('decision', 16);
            $t->unsignedInteger('revision');
            $t->text('private_reason');
            $t->timestamps();
            $t->unique(['school_id', 'reviewed_by', 'operation_id'], 'ai_flag_review_operation');
        });
        DB::table('permissions')->insertOrIgnore(['name' => 'ai-flags-review', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()]);
        $permission = DB::table('permissions')->where('name', 'ai-flags-review')->where('guard_name', 'web')->value('id');
        foreach (DB::table('roles')->where('name', 'Safeguarding')->where(fn ($q) => $q->whereNull('custom_role')->orWhere('custom_role', 0))->pluck('id') as $role) {
            DB::table('role_has_permissions')->insertOrIgnore(['role_id' => $role, 'permission_id' => $permission]);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        if (DB::table('ai_flag_reviews')->exists()) {
            throw new RuntimeException('Private AI review history must be retained until its flag expires.');
        }
        Schema::dropIfExists('ai_flag_reviews');
        Schema::table('ai_flags', fn (Blueprint $t) => $t->dropColumn(['review_revision', 'review_status']));
        // Keep explicit grants: role history is not reverted by a schema rollback.
    }
};
