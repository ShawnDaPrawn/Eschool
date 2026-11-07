<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The guided learning path (Action Plan 3.9): one path per learner and
 * learning group, the questions it gave them, and the review it scheduled
 * for each topic the learner passed. All three tables are school-owned.
 * engineering/specs/learning/learning-path.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_paths', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('learner_id')->constrained('students')->restrictOnDelete();
            $t->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $t->foreignId('teaching_group_id')->constrained('teaching_groups')->restrictOnDelete();
            $t->foreignId('subject_id')->constrained('subjects')->restrictOnDelete();
            $t->foreignId('session_year_id')->constrained('session_years')->restrictOnDelete();
            $t->foreignId('topic_id')->nullable()->constrained('mastery_topics')->restrictOnDelete();
            $t->string('step', 12)->default('gap');
            $t->string('mode', 8)->default('learn');
            $t->string('reason', 24)->nullable();
            $t->string('notice', 24)->nullable();
            $t->unsignedInteger('cycle')->default(0);
            $t->unsignedTinyInteger('failed_checks')->default(0);
            $t->foreignId('rest_topic_id')->nullable()->constrained('mastery_topics')->restrictOnDelete();
            $t->date('rest_until')->nullable();
            $t->foreignUuid('tutor_session_id')->nullable()->constrained('tutor_sessions')->restrictOnDelete();
            $t->unsignedInteger('revision')->default(0);
            $t->timestamps();
            $t->unique(['school_id', 'learner_id', 'teaching_group_id'], 'learning_path_one_per_group');
            $t->index(['school_id', 'teaching_group_id', 'step'], 'learning_path_group_step');
        });
        Schema::create('learning_path_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('learning_path_id')->constrained('learning_paths')->restrictOnDelete();
            $t->foreignId('learner_id')->constrained('students')->restrictOnDelete();
            $t->foreignId('topic_id')->constrained('mastery_topics')->restrictOnDelete();
            $t->unsignedInteger('cycle');
            $t->string('purpose', 8);
            $t->unsignedTinyInteger('position');
            $t->foreignId('practice_item_version_id')->nullable()->constrained('practice_item_versions')->restrictOnDelete();
            $t->foreignId('library_item_version_id')->nullable()->constrained('library_item_versions')->restrictOnDelete();
            $t->uuid('answer_operation_id')->nullable();
            $t->longText('answer')->nullable();
            $t->boolean('correct')->nullable();
            $t->json('schedule')->nullable();
            $t->dateTime('answered_at')->nullable();
            $t->timestamps();
            $t->unique(['learning_path_id', 'cycle', 'purpose', 'position'], 'learning_path_item_position');
            $t->index(['school_id', 'learning_path_id', 'cycle'], 'learning_path_item_cycle');
        });
        Schema::create('learning_path_reviews', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('learning_path_id')->constrained('learning_paths')->restrictOnDelete();
            $t->foreignId('topic_id')->constrained('mastery_topics')->restrictOnDelete();
            $t->json('state');
            $t->date('reviewed_on');
            $t->date('due_on');
            $t->timestamps();
            $t->unique(['learning_path_id', 'topic_id'], 'learning_path_review_topic');
            $t->index(['school_id', 'learning_path_id', 'due_on'], 'learning_path_review_due');
        });
    }

    public function down(): void
    {
        foreach (['learning_path_reviews', 'learning_path_items', 'learning_paths'] as $table) {
            if (DB::table($table)->exists()) {
                throw new RuntimeException('Learning path history must be exported before rollback.');
            }
        }
        foreach (['learning_path_reviews', 'learning_path_items', 'learning_paths'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
