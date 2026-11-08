<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Practice tests (Action Plan 3.17): a blueprint says what a test is made of
 * (kind, topics and their shares, question count, time limit, window); a
 * practice test is one learner's timed sitting of it, with its questions
 * chosen from the school's bank and the platform library. All three tables
 * belong to a school. Answers are encrypted and never leave the server
 * before the sitting is handed in.
 */
return new class extends Migration
{
    private const TABLES = ['practice_test_questions', 'practice_tests', 'practice_test_blueprints'];

    public function up(): void
    {
        Schema::create('practice_test_blueprints', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('teaching_group_id')->constrained('teaching_groups')->restrictOnDelete();
            $t->foreignId('subject_id')->constrained('subjects')->restrictOnDelete();
            $t->unsignedBigInteger('grade_level_id');
            $t->foreignId('session_year_id')->constrained('session_years')->restrictOnDelete();
            $t->string('kind', 16);
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            // A quick quiz is the learner's own; a cycle test is set for the whole group.
            $t->foreignId('learner_id')->nullable()->constrained('students')->restrictOnDelete();
            $t->string('title', 191)->nullable();
            $t->json('topic_ids');
            $t->string('weighting', 16);
            $t->json('paper_version_ids')->nullable();
            $t->json('topic_shares');
            $t->unsignedSmallInteger('question_count');
            $t->unsignedInteger('time_limit_seconds')->nullable();
            $t->dateTime('opens_at')->nullable();
            $t->dateTime('closes_at')->nullable();
            $t->boolean('active')->default(true);
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
            $t->index(['school_id', 'teaching_group_id', 'kind', 'active'], 'practice_test_blueprint_group');
        });
        Schema::create('practice_tests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('practice_test_blueprint_id')->constrained('practice_test_blueprints')->restrictOnDelete();
            $t->foreignId('learner_id')->constrained('students')->restrictOnDelete();
            $t->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $t->foreignId('teaching_group_id')->constrained('teaching_groups')->restrictOnDelete();
            $t->unsignedInteger('time_limit_seconds');
            $t->dateTime('started_at');
            $t->dateTime('deadline_at');
            $t->dateTime('submitted_at')->nullable();
            // learner: handed in before time was up; time: handed in after it, so only answers saved in time count.
            $t->string('closed_by', 8)->nullable();
            $t->unsignedSmallInteger('correct_count')->nullable();
            $t->unsignedSmallInteger('marked_count')->nullable();
            $t->timestamps();
            $t->unique(['school_id', 'practice_test_blueprint_id', 'learner_id'], 'practice_test_one_sitting');
            $t->index(['school_id', 'learner_id', 'teaching_group_id', 'submitted_at'], 'practice_test_learner');
        });
        Schema::create('practice_test_questions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('practice_test_id')->constrained('practice_tests')->restrictOnDelete();
            $t->unsignedSmallInteger('position');
            $t->foreignId('practice_item_version_id')->nullable()->constrained('practice_item_versions')->restrictOnDelete();
            $t->foreignId('library_item_version_id')->nullable()->constrained('library_item_versions')->restrictOnDelete();
            $t->foreignId('topic_id')->constrained('mastery_topics')->restrictOnDelete();
            $t->longText('answer')->nullable();
            $t->dateTime('answered_at')->nullable();
            // auto: marked by the choice and number marker; awaiting_marker: a kind no marker handles yet.
            $t->string('marking', 16)->nullable();
            $t->boolean('correct')->nullable();
            $t->timestamps();
            $t->unique(['practice_test_id', 'position']);
        });
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            if (DB::table($table)->exists()) {
                throw new RuntimeException('Practice test history must be exported before rollback.');
            }
        }
        foreach (self::TABLES as $table) {
            Schema::dropIfExists($table);
        }
    }
};
