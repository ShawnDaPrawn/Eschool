<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learner_activity_days', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('learner_id')->constrained('students');
            $t->foreignId('grade_level_id')->constrained('grade_levels');
            $t->foreignId('session_year_id')->constrained('session_years');
            $t->date('on_date');
            $t->unique(['school_id', 'learner_id', 'on_date'], 'learner_activity_day_unique');
            $t->index(['school_id', 'session_year_id', 'on_date'], 'learner_activity_day_range');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learner_activity_days');
    }
};
