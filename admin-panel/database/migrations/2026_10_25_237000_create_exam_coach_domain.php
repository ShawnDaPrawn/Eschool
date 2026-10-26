<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('past_papers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('subject_id')->constrained('subjects')->restrictOnDelete();
            $t->foreignId('grade_level_id')->constrained('grade_levels')->restrictOnDelete();
            $t->string('framework', 32);
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->uuid('published_version_id')->nullable();
            $t->unsignedInteger('revision')->default(1);
            $t->boolean('active')->default(true);
            $t->text('withdrawal_reason')->nullable();
            $t->foreignId('withdrawn_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestamp('withdrawn_at')->nullable();
            $t->timestamps();
            $t->index(['school_id', 'subject_id', 'grade_level_id']);
        });
        Schema::create('past_paper_versions', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('past_paper_id')->constrained('past_papers')->restrictOnDelete();
            $t->unsignedInteger('number');
            $t->string('state', 16)->default('draft');
            $t->unsignedSmallInteger('paper_year');
            $t->string('paper_code', 64);
            $t->json('group_ids');
            $t->longText('definition');
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->foreignId('published_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestamp('published_at')->nullable();
            $t->date('licence_expires_on')->nullable();
            $t->string('fingerprint', 64);
            $t->timestamps();
            $t->unique(['school_id', 'past_paper_id', 'number'], 'past_paper_version_number');
        });
        Schema::table('past_papers', fn (Blueprint $t) => $t->foreign('published_version_id')->references('id')->on('past_paper_versions')->restrictOnDelete());
        Schema::create('exam_study_plans', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $t->foreignId('learner_id')->constrained('students')->restrictOnDelete();
            $t->foreignId('teaching_group_id')->constrained('teaching_groups')->restrictOnDelete();
            $t->foreignId('subject_id')->constrained('subjects')->restrictOnDelete();
            $t->foreignId('session_year_id')->constrained('session_years')->restrictOnDelete();
            $t->foreignId('exam_timetable_id')->constrained('exam_timetables')->restrictOnDelete();
            $t->string('schedule_signature', 64);
            $t->longText('definition');
            $t->timestamps();
        });
        Schema::create('exam_coach_operations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->uuid('operation_id');
            $t->string('request_hash', 64);
            $t->longText('result');
            $t->timestamps();
            $t->unique(['school_id', 'actor_id', 'operation_id'], 'exam_coach_operation_actor');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_coach_operations');
        Schema::dropIfExists('exam_study_plans');
        Schema::table('past_papers', fn (Blueprint $t) => $t->dropForeign(['published_version_id']));
        Schema::dropIfExists('past_paper_versions');
        Schema::dropIfExists('past_papers');
    }
};
