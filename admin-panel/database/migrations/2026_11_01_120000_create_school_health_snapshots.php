<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Nightly school health figures for HQ (Action Plan SA.10): one row per school
 * and day, counts and shares only, never a person. Sign-in tokens keep only
 * their latest use, so these rows are the only history of weekly active
 * families the at-risk trend can compare against.
 * engineering/specs/platform/hq-school-health.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_health_snapshots', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->date('snapshot_date')->comment("the school's own date");
            $t->unsignedInteger('guardians')->default(0);
            $t->unsignedInteger('active_guardians')->default(0)->comment('guardians with a sign-in or app use in the 7 days before');
            $t->unsignedInteger('learners')->default(0);
            $t->unsignedInteger('reached_learners')->default(0);
            $t->unsignedSmallInteger('flag_count')->default(0);
            $t->json('metrics');
            $t->json('flags');
            $t->timestamp('computed_at');
            $t->timestamps();
            $t->unique(['school_id', 'snapshot_date'], 'school_health_day');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('school_health_snapshots') && DB::table('school_health_snapshots')->exists()) {
            throw new LogicException('School health history must be retained.');
        }
        Schema::dropIfExists('school_health_snapshots');
    }
};
