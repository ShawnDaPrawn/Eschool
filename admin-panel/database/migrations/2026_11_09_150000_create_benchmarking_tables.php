<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Anonymous benchmarking (Action Plan 5.5).
 *
 * - benchmark_participations (school-owned): whether the school takes part.
 * - benchmark_school_values (school-owned): the school's own school-level
 *   figures for each weekly period, its comparison group and whether it was
 *   counted in that group's published figures. Never a learner.
 * - benchmark_releases (platform-owned, no school): the published median and
 *   quartiles of each comparison group, period and figure. Names no school.
 * - benchmark_runs (platform-owned, no school): the weekly snapshots that
 *   completed, so the latest week is known even when it published nothing.
 *
 * engineering/specs/leadership/benchmarking.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('benchmark_participations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->unique()->constrained('schools')->restrictOnDelete();
            $t->boolean('opted_in');
            $t->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('changed_at');
            $t->timestamp('left_at')->nullable();
            $t->timestamps();
        });
        Schema::create('benchmark_school_values', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->date('period_end');
            $t->string('metric', 24);
            // sector:stages:band, or null when the school cannot be placed in a group.
            $t->string('group_key', 64)->nullable();
            $t->unsignedSmallInteger('value_bp')->nullable();
            $t->boolean('contributed')->default(false);
            $t->timestamp('computed_at');
            $t->timestamps();
            $t->unique(['school_id', 'period_end', 'metric'], 'benchmark_school_value_identity');
            $t->index(['period_end', 'metric', 'group_key'], 'benchmark_school_value_group');
        });
        Schema::create('benchmark_releases', function (Blueprint $t) {
            $t->id();
            $t->date('period_end');
            $t->string('group_key', 64);
            $t->string('metric', 24);
            // released: new figures; held: the last published figures kept (differencing guard).
            $t->string('status', 16);
            $t->date('basis_period_end');
            $t->unsignedSmallInteger('lower_quartile_bp');
            $t->unsignedSmallInteger('median_bp');
            $t->unsignedSmallInteger('upper_quartile_bp');
            $t->timestamp('computed_at');
            $t->timestamps();
            $t->unique(['period_end', 'group_key', 'metric'], 'benchmark_release_identity');
            $t->index(['group_key', 'metric', 'period_end'], 'benchmark_release_history');
        });
        Schema::create('benchmark_runs', function (Blueprint $t) {
            $t->id();
            $t->date('period_end')->unique();
            $t->timestamp('completed_at');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['benchmark_runs', 'benchmark_releases', 'benchmark_school_values', 'benchmark_participations'] as $table) {
            if (Schema::hasTable($table) && DB::table($table)->exists()) {
                throw new RuntimeException('Restore a tested backup to retain benchmarking records.');
            }
        }
        Schema::dropIfExists('benchmark_runs');
        Schema::dropIfExists('benchmark_releases');
        Schema::dropIfExists('benchmark_school_values');
        Schema::dropIfExists('benchmark_participations');
    }
};
