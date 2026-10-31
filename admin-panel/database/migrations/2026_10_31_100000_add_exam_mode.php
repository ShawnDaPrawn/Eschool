<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Exam mode (Action Plan 1.8): a venue and a change counter on each exam
 * sitting, and per-grade exam periods during which a learner's week shows
 * exam days. engineering/specs/academics/exam-mode.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_timetables', function (Blueprint $table) {
            $table->string('venue', 120)->nullable()->after('end_time');
            $table->unsignedInteger('revision')->default(1)->after('venue');
        });

        Schema::create('exam_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools');
            $table->unsignedBigInteger('session_year_id');
            $table->unsignedBigInteger('grade_level_id');
            $table->string('name', 120);
            $table->date('starts_on');
            $table->date('ends_on');
            $table->boolean('lessons_continue')->default(false)->comment('Whether normal lessons still show on exam days');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'session_year_id', 'grade_level_id'], 'exam_periods_school_year_grade_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_periods');
        Schema::table('exam_timetables', function (Blueprint $table) {
            $table->dropColumn(['venue', 'revision']);
        });
    }
};
