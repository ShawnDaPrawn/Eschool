<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('permissions')->insertOrIgnore(['name' => 'pathways-manage', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()]);
        $permission = DB::table('permissions')->where('name', 'pathways-manage')->where('guard_name', 'web')->value('id');
        foreach (DB::table('roles')->where('name', 'Super Admin')->where(fn ($q) => $q->whereNull('custom_role')->orWhere('custom_role', 0))->pluck('id') as $role) {
            DB::table('role_has_permissions')->insertOrIgnore(['role_id' => $role, 'permission_id' => $permission]);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Schema::create('admission_requirements', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('school_id')->constrained('schools');
            $t->string('kind', 24);
            $t->string('status', 16);
            $t->unsignedInteger('revision');
            $t->unsignedBigInteger('current_version_id')->nullable();
            $t->date('expires_on');
            $t->timestamps();
            $t->unique(['school_id', 'id']);
            $t->index(['school_id', 'kind', 'status', 'expires_on']);
        });
        Schema::create('admission_requirement_versions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->uuid('requirement_id');
            $t->unsignedInteger('revision');
            $t->string('status', 16);
            $t->string('title', 191);
            $t->string('institution', 191)->nullable();
            $t->string('source_title', 240);
            $t->text('source_url');
            $t->unsignedSmallInteger('source_year');
            $t->date('reviewed_on');
            $t->date('expires_on');
            $t->text('guidance');
            $t->json('rules');
            $t->foreignId('reviewed_by')->constrained('users');
            $t->timestamps();
            $t->unique(['school_id', 'id']);
            $t->unique(['school_id', 'requirement_id', 'id'], 'pathway_version_identity');
            $t->unique(['school_id', 'requirement_id', 'revision'], 'pathway_source_revision');
            $t->foreign(['school_id', 'requirement_id'])->references(['school_id', 'id'])->on('admission_requirements');
        });
        Schema::table('admission_requirements', function (Blueprint $t) {
            $t->foreign(['school_id', 'id', 'current_version_id'], 'pathway_current_version')->references(['school_id', 'requirement_id', 'id'])->on('admission_requirement_versions');
        });
        Schema::create('pathway_operations', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('actor_id')->constrained('users');
            $t->uuid('operation_id');
            $t->char('request_hash', 64);
            $t->json('receipt');
            $t->timestamps();
            $t->unique(['school_id', 'actor_id', 'operation_id'], 'pathway_operation_identity');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('admission_requirement_versions') && DB::table('admission_requirement_versions')->exists()) {
            throw new RuntimeException('Pathway rollback refused: preserve published source history.');
        }
        Schema::dropIfExists('pathway_operations');
        if (Schema::hasTable('admission_requirements')) {
            Schema::table('admission_requirements', fn (Blueprint $t) => $t->dropForeign('pathway_current_version'));
        }
        Schema::dropIfExists('admission_requirement_versions');
        Schema::dropIfExists('admission_requirements');
    }
};
