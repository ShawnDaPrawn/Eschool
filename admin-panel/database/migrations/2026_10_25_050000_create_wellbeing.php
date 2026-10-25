<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const TABLES = ['wellbeing_events', 'wellbeing_operations', 'wellbeing_appointments', 'support_case_entries', 'support_case_assignments', 'support_cases', 'incident_threads', 'incident_reports', 'wellbeing_team_assignments', 'wellbeing_categories'];

    public function up(): void
    {
        Schema::create('wellbeing_categories', function (Blueprint $t) {
            $this->identity($t);
            $t->string('name', 191);
            $t->text('emergency_guidance');
            $t->boolean('active')->default(true);
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
        });
        Schema::create('wellbeing_team_assignments', function (Blueprint $t) {
            $this->identity($t);
            $t->foreignId('category_id')->constrained('wellbeing_categories');
            $t->foreignId('user_id')->constrained('users');
            $this->dates($t);
        });
        Schema::create('incident_reports', function (Blueprint $t) {
            $this->identity($t);
            $t->foreignId('category_id')->constrained('wellbeing_categories');
            $t->boolean('anonymous')->default(true);
            $t->foreignId('reporter_user_id')->nullable()->constrained('users');
            $t->foreignId('learner_id')->nullable()->constrained('students');
            $t->string('secret_digest', 64);
            $t->string('submission_hash', 64);
            $t->longText('body');
            $t->string('urgency', 16);
            $t->string('status', 24)->default('open');
            $t->timestamp('identified_at')->nullable();
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
            $t->unique(['school_id', 'secret_digest'], 'incident_secret_scope');
        });
        Schema::create('incident_threads', function (Blueprint $t) {
            $this->identity($t);
            $t->foreignId('incident_report_id')->constrained('incident_reports');
            $t->foreignId('staff_user_id')->nullable()->constrained('users');
            $t->string('author_type', 16);
            $t->boolean('internal')->default(false);
            $t->longText('body');
            $t->uuid('operation_id');
            $t->string('request_hash', 64);
            $t->timestamps();
            $t->unique(['school_id', 'incident_report_id', 'operation_id'], 'incident_thread_retry');
        });
        Schema::create('support_cases', function (Blueprint $t) {
            $this->identity($t);
            $t->foreignId('learner_id')->constrained('students');
            $t->foreignId('opened_by')->constrained('users');
            $t->foreignId('incident_report_id')->nullable()->constrained('incident_reports');
            $t->string('status', 24)->default('open');
            $t->timestamp('opened_at');
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
        });
        Schema::create('support_case_assignments', function (Blueprint $t) {
            $this->identity($t);
            $t->foreignId('support_case_id')->constrained('support_cases');
            $t->foreignId('user_id')->constrained('users');
            $this->dates($t);
        });
        Schema::create('support_case_entries', function (Blueprint $t) {
            $this->identity($t);
            $t->foreignId('support_case_id')->constrained('support_cases');
            $t->foreignId('author_id')->constrained('users');
            $t->foreignId('supersedes_id')->nullable()->constrained('support_case_entries');
            $t->string('kind', 24);
            $t->longText('body');
            $t->timestamp('happened_at');
            $t->timestamps();
        });
        Schema::create('wellbeing_appointments', function (Blueprint $t) {
            $this->identity($t);
            $t->foreignId('support_case_id')->constrained('support_cases');
            $t->foreignId('professional_user_id')->constrained('users');
            $t->timestamp('starts_at');
            $t->timestamp('ends_at');
            $t->text('location');
            $t->longText('notes');
            $t->string('status', 24)->default('scheduled');
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
            $t->index(['school_id', 'professional_user_id', 'starts_at'], 'wellbeing_appointment_calendar');
        });
        Schema::create('wellbeing_operations', function (Blueprint $t) {
            $this->identity($t);
            $t->foreignId('actor_id')->constrained('users');
            $t->uuid('operation_id');
            $t->string('request_hash', 64);
            $t->longText('receipt');
            $t->timestamps();
            $t->unique(['school_id', 'actor_id', 'operation_id'], 'wellbeing_operation_retry');
        });
        Schema::create('wellbeing_events', function (Blueprint $t) {
            $this->identity($t);
            $t->foreignId('actor_id')->nullable()->constrained('users');
            $t->string('event', 64);
            $t->string('subject_type', 64);
            $t->longText('snapshot');
            $t->unsignedBigInteger('subject_id');
            $t->unsignedInteger('revision');
            $t->timestamps();
        });
        DB::table('permissions')->insertOrIgnore(['name' => 'wellbeing-configure', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()]);
        $permission = DB::table('permissions')->where('name', 'wellbeing-configure')->where('guard_name', 'web')->value('id');
        foreach (DB::table('roles')->where('name', 'Super Admin')->where(fn ($q) => $q->whereNull('custom_role')->orWhere('custom_role', 0))->pluck('id') as $role) {
            DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $permission, 'role_id' => $role]);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function identity(Blueprint $table): void
    {
        $table->id();
        $table->foreignId('school_id')->constrained('schools');
    }

    private function dates(Blueprint $table): void
    {
        $table->date('starts_on');
        $table->date('ends_on');
        $table->boolean('active')->default(true);
        $table->text('reason');
        $table->unsignedInteger('revision')->default(1);
        $table->timestamps();
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            if (DB::table($table)->exists()) {
                throw new RuntimeException('Retained wellbeing records prevent destructive rollback.');
            }
        }
        foreach (self::TABLES as $table) {
            Schema::drop($table);
        }
        // Preserve permissions referenced by custom role grants.
    }
};
