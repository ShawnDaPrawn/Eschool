<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The daily family summary (Action Plan 5.11). A guardian's own opt-in and
 * time (one setting for every school), one summary per guardian per local
 * day, and one school-owned part per school the summary covers.
 * engineering/specs/notifications/family-daily-summary.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('family_summary_preferences', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $t->boolean('enabled')->default(false);
            $t->time('send_at');
            $t->string('timezone', 64);
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
            $t->index(['enabled', 'id'], 'family_summary_enabled');
        });
        Schema::create('family_daily_summaries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->date('summary_date');
            $t->string('state', 16)->default('composing');
            $t->foreignId('notice_school_id')->nullable()->constrained('schools')->restrictOnDelete();
            // The notice lives in the notice school; no foreign key, so the
            // notifications table's own retention and rollbacks are unchanged.
            $t->unsignedBigInteger('notification_id')->nullable();
            $t->timestamp('sent_at')->nullable();
            $t->timestamp('expires_at');
            $t->timestamps();
            $t->unique(['user_id', 'summary_date'], 'family_summary_once_a_day');
            $t->index(['expires_at'], 'family_summary_retention');
        });
        Schema::create('family_daily_summary_parts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('family_daily_summary_id')->constrained('family_daily_summaries')->cascadeOnDelete();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            // Persisted so a retry reuses the same AI receipt instead of a new one.
            $t->uuid('operation_id');
            $t->longText('facts');
            $t->char('facts_hash', 64);
            $t->text('learner_ids');
            $t->text('text');
            $t->string('wording', 16);
            $t->string('fallback_reason', 64)->nullable();
            $t->timestamp('expires_at');
            $t->timestamps();
            $t->unique(['school_id', 'family_daily_summary_id'], 'family_summary_one_part_per_school');
            $t->unique(['school_id', 'operation_id'], 'family_summary_part_operation');
            $t->index(['school_id', 'expires_at'], 'family_summary_part_retention');
        });
    }

    public function down(): void
    {
        if (DB::table('family_daily_summaries')->exists()) {
            throw new RuntimeException('Family summaries still exist. Let retention erase them before rolling back.');
        }
        Schema::dropIfExists('family_daily_summary_parts');
        Schema::dropIfExists('family_daily_summaries');
        Schema::dropIfExists('family_summary_preferences');
    }
};
