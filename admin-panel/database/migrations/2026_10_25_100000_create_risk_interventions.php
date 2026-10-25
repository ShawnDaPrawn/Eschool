<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risk_settings', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->unique()->constrained('schools')->restrictOnDelete();
            $t->unsignedInteger('academic_threshold_bp')->default(6500);
            $t->unsignedInteger('minimum_coverage_bp')->default(2500);
            $t->unsignedInteger('absence_threshold_bp')->default(1000);
            $t->unsignedInteger('minimum_registers')->default(10);
            $t->unsignedInteger('revision');
            $t->timestamps();
        });
        Schema::create('risk_observations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('learner_id')->constrained('students')->restrictOnDelete();
            $t->foreignId('grade_level_id')->constrained('grade_levels')->restrictOnDelete();
            $t->foreignId('session_year_id')->constrained('session_years')->restrictOnDelete();
            $t->date('as_of');
            $t->date('window_start');
            $t->string('source', 24);
            $t->unsignedInteger('score')->nullable();
            $t->unsignedInteger('signal_count')->nullable();
            $t->string('reason_code', 48);
            $t->longText('evidence');
            $t->string('fingerprint', 64);
            $t->timestamp('created_at');
            $t->unique(['school_id', 'learner_id', 'grade_level_id', 'session_year_id', 'as_of', 'source', 'fingerprint'], 'risk_observation_evidence');
            $t->index(['school_id', 'session_year_id', 'as_of', 'grade_level_id', 'source'], 'risk_observation_metrics');
        });
        Schema::create('risk_caseloads', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('learner_id')->constrained('students')->restrictOnDelete();
            $t->foreignId('session_year_id')->constrained('session_years')->restrictOnDelete();
            $t->foreignId('professional_id')->constrained('users')->restrictOnDelete();
            $t->date('starts_on');
            $t->date('ends_on');
            $t->timestamp('revoked_at')->nullable();
            $t->unsignedInteger('revision');
            $t->timestamps();
            $t->unique(['school_id', 'learner_id', 'session_year_id', 'professional_id'], 'risk_caseload_owner');
        });
        Schema::create('interventions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('learner_id')->constrained('students')->restrictOnDelete();
            $t->foreignId('session_year_id')->constrained('session_years')->restrictOnDelete();
            $t->foreignId('grade_level_id')->constrained('grade_levels')->restrictOnDelete();
            $t->foreignId('owner_id')->constrained('users')->restrictOnDelete();
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->string('owner_authority', 24);
            $t->date('started_on');
            $t->date('review_on');
            $t->string('state', 24)->default('active');
            $t->longText('action');
            $t->longText('outcome')->nullable();
            $t->unsignedInteger('revision');
            $t->timestamps();
            $t->index(['school_id', 'owner_id', 'state', 'review_on'], 'intervention_review_queue');
        });
        Schema::create('intervention_versions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('intervention_id')->constrained('interventions')->restrictOnDelete();
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->unsignedInteger('revision');
            $t->longText('payload');
            $t->timestamp('created_at');
            $t->unique(['school_id', 'intervention_id', 'revision'], 'intervention_version');
        });
    }

    public function down(): void
    {
        $tables = ['intervention_versions', 'interventions', 'risk_caseloads', 'risk_observations', 'risk_settings'];
        foreach ($tables as $table) {
            if (DB::table($table)->exists()) {
                throw new LogicException('Retain early-warning and intervention history.');
            }
        }
        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }
    }
};
