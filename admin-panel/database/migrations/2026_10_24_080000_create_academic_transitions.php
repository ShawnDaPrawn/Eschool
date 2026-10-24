<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrolments', fn (Blueprint $table) => $table->softDeletes());
        Schema::create('academic_transition_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools');
            $table->foreignId('grade_level_id')->constrained('grade_levels');
            $table->string('join_mark_rule', 16)->default('catch_up');
            $table->string('change_mark_rule', 16)->default('catch_up');
            $table->unsignedInteger('revision')->default(1);
            $table->timestamps();
            $table->unique(['school_id', 'grade_level_id'], 'transition_grade_policy');
        });
        Schema::create('academic_transitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools');
            $table->foreignId('learner_id')->constrained('students');
            $table->foreignId('session_year_id')->constrained('session_years');
            $table->foreignId('grade_level_id')->nullable()->constrained('grade_levels');
            $table->foreignId('actor_id')->constrained('users');
            $table->uuid('operation_id');
            $table->string('request_hash', 64);
            $table->string('kind', 20);
            $table->date('effective_on');
            $table->text('reason');
            $table->longText('payload');
            $table->longText('result')->nullable();
            $table->timestamps();
            $table->unique(['school_id', 'actor_id', 'operation_id'], 'transition_operation');
            $table->index(['school_id', 'learner_id', 'effective_on'], 'transition_learner_dates');
        });
        Schema::create('academic_exit_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools');
            $table->foreignId('academic_transition_id')->constrained('academic_transitions');
            $table->foreignId('learner_id')->constrained('students');
            $table->foreignId('requested_by')->constrained('users');
            $table->uuid('operation_id');
            $table->uuid('generation_id');
            $table->string('request_hash', 64);
            $table->string('source_fingerprint', 64);
            $table->string('state', 16)->default('queued');
            $table->longText('blueprint')->nullable();
            $table->longText('manifest')->nullable();
            $table->string('artifact_path', 255)->nullable();
            $table->string('artifact_sha256', 64)->nullable();
            $table->unsignedBigInteger('artifact_bytes')->nullable();
            $table->string('signing_public_key', 100)->nullable();
            $table->string('signing_key_id', 64)->nullable();
            $table->string('failure_code', 64)->nullable();
            $table->dateTime('expires_at');
            $table->dateTime('generated_at')->nullable();
            $table->timestamps();
            $table->unique(['school_id', 'requested_by', 'operation_id'], 'exit_package_operation');
            $table->index(['school_id', 'state', 'expires_at'], 'exit_package_expiry');
        });
        Schema::create('retention_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools');
            $table->string('record_type', 64);
            $table->string('action', 32);
            $table->unsignedInteger('days')->nullable();
            $table->unsignedInteger('revision')->default(1);
            $table->timestamps();
            $table->unique(['school_id', 'record_type'], 'school_retention_rule');
        });
    }

    public function down(): void
    {
        if (DB::table('academic_transitions')->exists() || DB::table('academic_exit_packages')->exists() || DB::table('enrolments')->whereNotNull('deleted_at')->exists()) {
            throw new LogicException('Transition and cancelled enrolment history must be retained.');
        }
        Schema::dropIfExists('retention_rules');
        Schema::dropIfExists('academic_exit_packages');
        Schema::dropIfExists('academic_transitions');
        Schema::dropIfExists('academic_transition_policies');
        Schema::table('enrolments', fn (Blueprint $table) => $table->dropSoftDeletes());
    }
};
