<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The diagnostic start (Action Plan 3.7): a learner's attempt at a published
 * library diagnostic, and the questions planned for it. Both are the school's
 * own rows. They point at the platform library (read only); nothing about a
 * learner is ever written to a library table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diagnostic_attempts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('learner_id')->constrained('students')->restrictOnDelete();
            $t->foreignId('teaching_group_id')->constrained('teaching_groups')->restrictOnDelete();
            $t->foreignId('subject_id')->constrained('subjects')->restrictOnDelete();
            $t->foreignId('grade_level_id')->constrained('grade_levels')->restrictOnDelete();
            $t->foreignId('session_year_id')->constrained('session_years')->restrictOnDelete();
            $t->foreignId('library_item_id')->constrained('library_items')->restrictOnDelete();
            $t->foreignId('library_item_version_id')->constrained('library_item_versions')->restrictOnDelete();
            $t->foreignId('started_by')->constrained('users')->restrictOnDelete();
            $t->dateTime('completed_at')->nullable();
            $t->timestamps();
            // One attempt per learner, diagnostic and school year: starting again resumes it.
            $t->unique(['school_id', 'learner_id', 'library_item_id', 'session_year_id'], 'diagnostic_attempt_identity');
            $t->index(['school_id', 'teaching_group_id'], 'diagnostic_attempt_group');
        });
        Schema::create('diagnostic_attempt_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('diagnostic_attempt_id')->constrained('diagnostic_attempts')->restrictOnDelete();
            $t->unsignedSmallInteger('position');
            $t->foreignId('library_item_id')->constrained('library_items')->restrictOnDelete();
            $t->foreignId('library_item_version_id')->constrained('library_item_versions')->restrictOnDelete();
            $t->foreignId('library_topic_id')->constrained('library_topics')->restrictOnDelete();
            $t->foreignId('topic_id')->constrained('mastery_topics')->restrictOnDelete();
            // waiting, answered or skipped (the topic was already clear).
            $t->string('state', 16)->default('waiting');
            // Encrypted, as daily practice answers are.
            $t->text('answer')->nullable();
            $t->boolean('correct')->nullable();
            $t->dateTime('answered_at')->nullable();
            $t->timestamps();
            $t->unique(['diagnostic_attempt_id', 'position'], 'diagnostic_item_position');
            $t->unique(['diagnostic_attempt_id', 'library_item_id'], 'diagnostic_item_once');
        });
    }

    public function down(): void
    {
        if (DB::table('diagnostic_attempts')->exists()) {
            throw new RuntimeException('Diagnostic attempts must be exported before rollback.');
        }
        Schema::dropIfExists('diagnostic_attempt_items');
        Schema::dropIfExists('diagnostic_attempts');
    }
};
