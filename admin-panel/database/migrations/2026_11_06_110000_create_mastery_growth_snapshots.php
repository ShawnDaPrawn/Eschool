<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Mastery growth snapshots (Action Plan 3.14): one row per learner, topic,
 * term, audience and mastery model version, holding the derived mastery at
 * the start of the term and at its end (or the latest so far). A missing
 * level or score is "no evidence", never zero.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mastery_growth_snapshots', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('term_id')->constrained('terms');
            $t->foreignId('session_year_id')->constrained('session_years');
            $t->foreignId('subject_id')->constrained('subjects');
            $t->foreignId('topic_id')->constrained('mastery_topics');
            $t->foreignId('grade_level_id')->constrained('grade_levels');
            $t->foreignId('learner_id')->constrained('students');
            $t->string('audience', 16);
            $t->string('policy_version', 32);
            // replayed: rebuilt from evidence dated before the term; captured: read on the term's first day; unavailable: unknown.
            $t->string('start_basis', 16);
            $t->unsignedTinyInteger('start_level')->nullable();
            $t->unsignedSmallInteger('start_score_bp')->nullable();
            $t->unsignedSmallInteger('start_confidence_bp')->nullable();
            $t->unsignedSmallInteger('start_evidence_count')->nullable();
            $t->unsignedTinyInteger('end_level')->nullable();
            $t->unsignedSmallInteger('end_score_bp')->nullable();
            $t->unsignedSmallInteger('end_confidence_bp')->nullable();
            $t->unsignedSmallInteger('end_evidence_count')->nullable();
            $t->date('end_as_of');
            $t->boolean('end_final')->default(false);
            $t->timestamp('captured_at');
            $t->timestamps();
            $t->unique(['school_id', 'term_id', 'learner_id', 'topic_id', 'audience', 'policy_version'], 'mastery_growth_identity');
            $t->index(['school_id', 'term_id', 'policy_version', 'audience', 'grade_level_id'], 'mastery_growth_report');
        });
    }

    public function down(): void
    {
        if (DB::table('mastery_growth_snapshots')->exists()) {
            throw new RuntimeException('Retained mastery growth snapshots block destructive rollback.');
        }
        Schema::dropIfExists('mastery_growth_snapshots');
    }
};
