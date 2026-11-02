<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Action Plan 4.6: growth-first weekly digest blocks and persistence badges. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('weekly_parent_digests', function (Blueprint $t): void {
            // Encrypted topic names behind the "grew in" and help-at-home lines.
            $t->longText('growth')->nullable()->after('home_action');
        });
        Schema::create('learner_growth_weeks', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('learner_id')->constrained('students');
            $t->foreignId('user_id')->constrained('users');
            $t->date('week_start');
            $t->longText('topics');
            $t->string('policy_version', 32);
            $t->foreignId('notification_id')->nullable()->constrained('notifications')->nullOnDelete();
            $t->timestamp('expires_at');
            $t->timestamps();
            $t->unique(['school_id', 'learner_id', 'week_start'], 'learner_growth_once_a_week');
            $t->index(['school_id', 'user_id'], 'learner_growth_reader');
        });
        Schema::create('recognition_badge_settings', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->unique('recognition_badge_school_settings')->constrained('schools');
            $t->boolean('practice_days_enabled')->default(false);
            $t->unsignedTinyInteger('practice_days_required')->default(4);
            $t->boolean('comeback_enabled')->default(false);
            $t->unsignedTinyInteger('comeback_below_percent')->default(50);
            $t->foreignId('switched_on_by')->nullable()->constrained('users');
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
        });
        Schema::create('recognition_badges', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('learner_id')->constrained('students');
            $t->string('kind', 32);
            $t->date('week_start');
            $t->foreignId('topic_id')->nullable()->constrained('mastery_topics');
            $t->char('dedupe_key', 64);
            $t->foreignId('award_presentation_id')->nullable()->constrained('award_presentations');
            $t->timestamps();
            $t->unique(['school_id', 'dedupe_key'], 'recognition_badge_once');
            $t->index(['school_id', 'learner_id', 'week_start'], 'recognition_badge_week');
        });
    }

    public function down(): void
    {
        if (DB::table('recognition_badges')->exists() || DB::table('recognition_badge_settings')->exists() || DB::table('learner_growth_weeks')->exists() || DB::table('weekly_parent_digests')->whereNotNull('growth')->exists()) {
            throw new RuntimeException('Retained growth digests or persistence badges prevent rollback.');
        }
        Schema::dropIfExists('recognition_badges');
        Schema::dropIfExists('recognition_badge_settings');
        Schema::dropIfExists('learner_growth_weeks');
        Schema::table('weekly_parent_digests', function (Blueprint $t): void {
            $t->dropColumn('growth');
        });
    }
};
