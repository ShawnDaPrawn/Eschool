<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Short days: on one date, a bell schedule is replaced by another. Lessons keep
 * their placement and follow the replacement's teaching period in the same
 * order; lessons beyond its last teaching period do not take place that day.
 * Specification: engineering/specs/academics/short-days.md (Action Plan 1.7).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bell_schedule_overrides', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('session_year_id')->constrained('session_years')->restrictOnDelete();
            $t->date('on_date');
            $t->foreignId('bell_schedule_id')->constrained('bell_schedules')->restrictOnDelete();
            $t->foreignId('replacement_bell_schedule_id')->constrained('bell_schedules')->restrictOnDelete();
            $t->string('reason', 191);
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
            $t->unique(['school_id', 'bell_schedule_id', 'on_date']);
            $t->index(['school_id', 'on_date']);
        });
    }

    public function down(): void
    {
        if (DB::table('bell_schedule_overrides')->exists()) {
            throw new RuntimeException('Restore a tested backup to retain short-day history.');
        }
        Schema::dropIfExists('bell_schedule_overrides');
    }
};
