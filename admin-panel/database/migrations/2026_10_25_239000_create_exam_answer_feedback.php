<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_answers', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $t->foreignId('learner_id')->constrained('students')->restrictOnDelete();
            $t->foreignId('teaching_group_id')->constrained('teaching_groups')->restrictOnDelete();
            $t->foreignId('subject_id')->constrained('subjects')->restrictOnDelete();
            $t->foreignId('session_year_id')->constrained('session_years')->restrictOnDelete();
            $t->foreignUuid('paper_version_id')->constrained('past_paper_versions')->restrictOnDelete();
            $t->string('question_number', 16);
            $t->char('paper_fingerprint', 64);
            $t->uuid('operation_id');
            $t->char('upload_hash', 64);
            $t->char('request_hash', 64)->nullable();
            $t->string('state', 24)->default('prepared');
            $t->longText('working')->nullable();
            $t->longText('response')->nullable();
            $t->string('failure_code', 64)->nullable();
            $t->foreignId('ai_usage_id')->nullable()->constrained('ai_usage')->restrictOnDelete();
            $t->uuid('attempt_id')->nullable();
            $t->dateTime('lease_until')->nullable();
            $t->string('path', 512)->nullable();
            $t->char('sha256', 64);
            $t->unsignedInteger('bytes');
            $t->text('crop')->nullable();
            $t->boolean('share_with_teacher');
            $t->dateTime('expires_at');
            $t->dateTime('revoked_at')->nullable();
            $t->dateTime('purged_at')->nullable();
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
            $t->unique(['school_id', 'user_id', 'operation_id']);
            $t->index(['school_id', 'teaching_group_id', 'state']);
        });
        Schema::create('exam_answer_reviews', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignUuid('exam_answer_id')->constrained('exam_answers')->restrictOnDelete();
            $t->foreignId('reviewer_id')->constrained('users')->restrictOnDelete();
            $t->unsignedInteger('mark_units');
            $t->text('reason');
            $t->unsignedInteger('answer_revision');
            $t->timestamps();
            $t->index(['school_id', 'exam_answer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_answer_reviews');
        Schema::dropIfExists('exam_answers');
    }
};
