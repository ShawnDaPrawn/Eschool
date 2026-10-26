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
        foreach (['family-forms-manage', 'family-health-read', 'family-health-manage'] as $name) {
            DB::table('permissions')->insertOrIgnore(['name' => $name, 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()]);
            $permission = DB::table('permissions')->where('name', $name)->where('guard_name', 'web')->value('id');
            foreach (DB::table('roles')->where('name', 'Super Admin')->where(fn ($q) => $q->whereNull('custom_role')->orWhere('custom_role', 0))->pluck('id') as $role) {
                DB::table('role_has_permissions')->insertOrIgnore(['role_id' => $role, 'permission_id' => $permission]);
            }
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Schema::table('activity_groups', fn (Blueprint $t) => $t->unique(['school_id', 'id'], 'activity_groups_school_identity'));
        Schema::create('consent_requests', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('school_id')->constrained('schools');
            $t->unsignedBigInteger('session_year_id');
            $t->unsignedBigInteger('activity_group_id');
            $t->foreignId('created_by')->constrained('users');
            $t->text('title');
            $t->longText('wording');
            $t->char('fingerprint', 64);
            $t->dateTime('event_starts_at');
            $t->dateTime('event_ends_at');
            $t->dateTime('respond_by');
            $t->string('status', 16)->default('open');
            $t->unsignedInteger('revision')->default(1);
            $t->dateTime('cancelled_at')->nullable();
            $t->timestamps();
            $t->unique(['school_id', 'id']);
            $t->foreign(['school_id', 'session_year_id'])->references(['school_id', 'id'])->on('session_years');
            $t->foreign(['school_id', 'activity_group_id'])->references(['school_id', 'id'])->on('activity_groups');
        });
        Schema::create('consent_recipients', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('school_id')->constrained('schools');
            $t->uuid('request_id');
            $t->unsignedBigInteger('learner_id');
            $t->unsignedInteger('revision')->default(0);
            $t->timestamps();
            $t->unique(['school_id', 'id']);
            $t->unique(['school_id', 'request_id', 'learner_id']);
            $t->foreign(['school_id', 'request_id'])->references(['school_id', 'id'])->on('consent_requests');
            $t->foreign(['school_id', 'learner_id'])->references(['school_id', 'id'])->on('students');
        });
        Schema::create('consent_responses', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('school_id')->constrained('schools');
            $t->uuid('recipient_id');
            $t->foreignId('guardian_user_id')->constrained('users');
            $t->unsignedInteger('revision');
            $t->string('decision', 16);
            $t->text('signed_name');
            $t->char('request_fingerprint', 64);
            $t->char('signature', 64);
            $t->dateTime('signed_at');
            $t->timestamps();
            $t->unique(['school_id', 'id']);
            $t->unique(['school_id', 'recipient_id', 'revision']);
            $t->foreign(['school_id', 'recipient_id'])->references(['school_id', 'id'])->on('consent_recipients');
        });
        Schema::create('family_form_operations', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('actor_id')->constrained('users');
            $t->uuid('operation_id');
            $t->string('kind', 40);
            $t->char('request_hash', 64);
            $t->longText('receipt');
            $t->timestamps();
            $t->unique(['school_id', 'actor_id', 'operation_id']);
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('consent_requests') && DB::table('consent_requests')->exists()) {
            throw new RuntimeException('Consent rollback refused: retain signed family histories.');
        }
        foreach (['family_form_operations', 'consent_responses', 'consent_recipients', 'consent_requests'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('activity_groups', fn (Blueprint $t) => $t->dropUnique('activity_groups_school_identity'));
    }
};
