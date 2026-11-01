<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * AI spending caps set in HQ (Action Plan SA.15, owner decision of 31 October
 * 2026): the daily caps per school, per person and per learner of each AI
 * feature, in integer cents of ai.billing_currency, replacing the defaults in
 * config/ai.php from the moment they are saved, and the history of every
 * change with who, when, the old and new values and the reason. Platform
 * tables: no school_id, written only from /saas/ai. A school's own lower limit
 * stays in ai_school_limits and is never written here.
 * Specification: engineering/specs/platform/hq-ai.md.
 */
return new class extends Migration
{
    private const TABLES = ['platform_ai_cap_changes', 'platform_ai_caps'];

    public function up(): void
    {
        Schema::create('platform_ai_caps', function (Blueprint $t) {
            $t->id();
            $t->string('feature', 64);
            // school_daily_cents, actor_daily_cents or learner_daily_cents.
            $t->string('cap', 32);
            $t->unsignedInteger('cents');
            $t->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->unique(['feature', 'cap']);
        });
        Schema::create('platform_ai_cap_changes', function (Blueprint $t) {
            $t->id();
            $t->string('feature', 64);
            $t->string('cap', 32);
            // Null: the config/ai.php default applied (before) or applies again (after).
            $t->unsignedInteger('old_cents')->nullable();
            $t->unsignedInteger('new_cents')->nullable();
            // The config default when the change was made, so the history reads on its own.
            $t->unsignedInteger('default_cents')->nullable();
            $t->char('currency', 3);
            $t->string('reason', 500);
            $t->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('created_at')->useCurrent();
            $t->index(['feature', 'created_at'], 'platform_ai_cap_changes_feature_index');
        });
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            if (Schema::hasTable($table) && DB::table($table)->exists()) {
                throw new RuntimeException('Platform AI caps and their history are kept: restore a tested backup instead of rolling back.');
            }
        }
        foreach (self::TABLES as $table) {
            Schema::dropIfExists($table);
        }
    }
};
