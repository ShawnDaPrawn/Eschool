<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Action Plan 3.13: practice streaks, weekly practice goals, gentle nudges and
 * the "gap closed" celebration. engineering/specs/learning/practice-motivation.md.
 */
return new class extends Migration
{
    private const TABLES = ['mastery_gap_closures', 'mastery_topic_gaps', 'practice_nudges', 'practice_goal_weeks', 'practice_goals', 'practice_motivation_settings'];

    public function up(): void
    {
        Schema::table('recognition_badge_settings', function (Blueprint $t): void {
            // Streak milestone badges, presented through the persistence badge ledger. Off by default.
            $t->boolean('streak_enabled')->default(false)->after('comeback_below_percent');
        });
        Schema::create('practice_motivation_settings', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->unique('practice_motivation_school_settings')->constrained('schools');
            $t->boolean('guardian_nudge_enabled')->default(false);
            $t->boolean('learner_nudge_enabled')->default(false);
            $t->unsignedTinyInteger('nudge_after_school_days')->default(3);
            $t->foreignId('changed_by')->nullable()->constrained('users');
            $t->unsignedInteger('revision')->default(0);
            $t->timestamps();
        });
        Schema::create('practice_goals', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('learner_id')->constrained('students');
            // Null once the learner clears the goal: the row stays so its revision only increases.
            $t->unsignedTinyInteger('days_per_week')->nullable();
            $t->foreignId('set_by')->constrained('users');
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
            $t->unique(['school_id', 'learner_id'], 'practice_goal_per_learner');
        });
        Schema::create('practice_goal_weeks', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('learner_id')->constrained('students');
            $t->foreignId('user_id')->constrained('users');
            $t->date('week_start');
            $t->unsignedTinyInteger('days_target');
            $t->foreignId('notification_id')->nullable()->constrained('notifications')->nullOnDelete();
            $t->timestamps();
            $t->unique(['school_id', 'learner_id', 'week_start'], 'practice_goal_met_once_a_week');
            $t->index(['school_id', 'user_id'], 'practice_goal_week_reader');
        });
        Schema::create('practice_nudges', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('learner_id')->constrained('students');
            $t->foreignId('user_id')->constrained('users');
            $t->string('audience', 16);
            $t->date('week_start');
            $t->foreignId('notification_id')->nullable()->constrained('notifications')->nullOnDelete();
            $t->timestamps();
            // At most one nudge per person per week, whichever child it was about.
            $t->unique(['school_id', 'user_id', 'week_start'], 'practice_nudge_once_a_week');
            $t->index(['school_id', 'learner_id'], 'practice_nudge_learner');
        });
        Schema::create('mastery_topic_gaps', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('learner_id')->constrained('students');
            $t->foreignId('topic_id')->constrained('mastery_topics');
            $t->string('audience', 16);
            $t->string('policy_version', 32);
            $t->timestamp('opened_at');
            $t->timestamps();
            $t->unique(['school_id', 'learner_id', 'topic_id'], 'mastery_topic_gap_once');
        });
        Schema::create('mastery_gap_closures', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('learner_id')->constrained('students');
            $t->foreignId('topic_id')->constrained('mastery_topics');
            $t->foreignId('term_id')->nullable()->constrained('terms');
            $t->foreignId('session_year_id')->nullable()->constrained('session_years');
            $t->string('audience', 16);
            $t->string('policy_version', 32);
            $t->char('dedupe_key', 64);
            $t->timestamp('closed_at');
            $t->foreignId('notification_id')->nullable()->constrained('notifications')->nullOnDelete();
            $t->timestamps();
            $t->unique(['school_id', 'dedupe_key'], 'mastery_gap_closed_once_a_term');
            $t->index(['school_id', 'learner_id', 'closed_at'], 'mastery_gap_closure_learner');
        });
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            if (Schema::hasTable($table) && DB::table($table)->exists()) {
                throw new RuntimeException('Retained practice motivation records prevent rollback.');
            }
        }
        if (DB::table('recognition_badge_settings')->where('streak_enabled', true)->exists()) {
            throw new RuntimeException('Retained practice motivation records prevent rollback.');
        }
        foreach (self::TABLES as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('recognition_badge_settings', function (Blueprint $t): void {
            $t->dropColumn('streak_enabled');
        });
    }
};
