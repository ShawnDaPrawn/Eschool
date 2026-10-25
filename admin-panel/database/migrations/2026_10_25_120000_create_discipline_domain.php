<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discipline_frameworks', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('session_year_id')->constrained('session_years')->restrictOnDelete();
            $t->string('title');
            $t->longText('definition');
            $t->unsignedInteger('revision');
            $t->timestamps();
            $t->unique(['school_id', 'session_year_id'], 'discipline_framework_year');
        });
        Schema::create('discipline_framework_versions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('framework_id')->constrained('discipline_frameworks')->restrictOnDelete();
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->unsignedInteger('revision');
            $t->longText('definition');
            $t->timestamp('created_at');
            $t->unique(['school_id', 'framework_id', 'revision'], 'discipline_framework_version');
        });
        Schema::create('discipline_records', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('learner_id')->constrained('students')->restrictOnDelete();
            $t->foreignId('session_year_id')->constrained('session_years')->restrictOnDelete();
            $t->foreignId('grade_level_id')->constrained('grade_levels')->restrictOnDelete();
            $t->foreignId('framework_id')->constrained('discipline_frameworks')->restrictOnDelete();
            $t->unsignedInteger('framework_revision');
            $t->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $t->date('occurred_on');
            $t->string('infraction_code', 40);
            $t->string('level_code', 40);
            $t->string('sanction_code', 40)->nullable();
            $t->string('status', 24);
            $t->string('hearing_decision', 24)->nullable();
            $t->foreignId('hearing_decided_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->longText('description');
            $t->longText('staff_note')->nullable();
            $t->longText('basis');
            $t->longText('decision_reason')->nullable();
            $t->longText('family_reason')->nullable();
            $t->timestamp('published_at')->nullable();
            $t->timestamp('acknowledged_by_parent_at')->nullable();
            $t->foreignId('acknowledged_by_parent_id')->nullable()->constrained('users')->restrictOnDelete();
            $t->unsignedInteger('revision');
            $t->timestamps();
            $t->index(['school_id', 'session_year_id', 'grade_level_id', 'occurred_on', 'status'], 'discipline_grade_counts');
            $t->index(['school_id', 'learner_id', 'session_year_id', 'occurred_on'], 'discipline_learner_history');
        });
        Schema::create('discipline_record_versions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('record_id')->constrained('discipline_records')->restrictOnDelete();
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->unsignedInteger('revision');
            $t->longText('payload');
            $t->timestamp('created_at');
            $t->unique(['school_id', 'record_id', 'revision'], 'discipline_record_version');
        });
        Schema::create('discipline_acknowledgements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('record_id')->constrained('discipline_records')->restrictOnDelete();
            $t->foreignId('guardian_id')->constrained('users')->restrictOnDelete();
            $t->unsignedInteger('record_revision');
            $t->timestamp('acknowledged_at');
            $t->unique(['school_id', 'record_id', 'guardian_id', 'record_revision'], 'discipline_guardian_ack');
        });
        Schema::create('detention_sessions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('session_year_id')->constrained('session_years')->restrictOnDelete();
            $t->foreignId('grade_level_id')->constrained('grade_levels')->restrictOnDelete();
            $t->foreignId('supervisor_id')->constrained('users')->restrictOnDelete();
            $t->date('on_date');
            $t->string('timezone', 64);
            $t->string('starts_at', 5);
            $t->string('ends_at', 5);
            $t->longText('location');
            $t->unsignedInteger('capacity');
            $t->string('status', 24)->default('planned');
            $t->unsignedInteger('revision');
            $t->timestamps();
        });
        Schema::create('detention_entries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('session_id')->constrained('detention_sessions')->restrictOnDelete();
            $t->foreignId('record_id')->constrained('discipline_records')->restrictOnDelete();
            $t->foreignId('learner_id')->constrained('students')->restrictOnDelete();
            $t->string('attendance', 16)->nullable();
            $t->longText('reason')->nullable();
            $t->string('status', 16)->default('active');
            $t->unsignedInteger('revision');
            $t->timestamps();
            $t->unique(['school_id', 'session_id', 'record_id'], 'detention_record_slot');
            $t->unique(['school_id', 'session_id', 'learner_id'], 'detention_learner_slot');
        });
        Schema::create('detention_versions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('session_id')->constrained('detention_sessions')->restrictOnDelete();
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->unsignedInteger('revision');
            $t->longText('payload');
            $t->timestamp('created_at');
            $t->unique(['school_id', 'session_id', 'revision'], 'detention_version');
        });
    }

    public function down(): void
    {
        $tables = ['detention_versions', 'detention_entries', 'detention_sessions', 'discipline_acknowledgements', 'discipline_record_versions', 'discipline_records', 'discipline_framework_versions', 'discipline_frameworks'];
        foreach ($tables as $table) {
            if (DB::table($table)->exists()) {
                throw new LogicException('Retain discipline and detention history.');
            }
        }
        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }
    }
};
