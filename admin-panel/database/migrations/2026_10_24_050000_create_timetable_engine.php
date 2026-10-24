<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->string('name', 191);
            $t->string('building', 191)->nullable();
            $t->unsignedSmallInteger('capacity')->nullable();
            $t->string('type', 64)->default('classroom');
            $t->boolean('active')->default(true);
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
            $t->softDeletes();
            $t->index(['school_id', 'active']);
        });
        Schema::create('bell_schedules', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('session_year_id')->constrained('session_years')->restrictOnDelete();
            $t->string('name', 191);
            $t->string('section', 100);
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
            $t->softDeletes();
            $t->index(['school_id', 'session_year_id']);
        });
        Schema::create('bell_periods', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('bell_schedule_id')->constrained('bell_schedules')->restrictOnDelete();
            $t->string('name', 100);
            $t->unsignedSmallInteger('position');
            $t->time('starts_at');
            $t->time('ends_at');
            $t->boolean('is_break')->default(false);
            $t->timestamps();
            $t->unique(['school_id', 'bell_schedule_id', 'position']);
        });
        // `lessons` already stores legacy learning content. Keep its IDs intact.
        Schema::create('timetable_lessons', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('session_year_id')->constrained('session_years')->restrictOnDelete();
            $t->foreignId('term_id')->nullable()->constrained('terms')->restrictOnDelete();
            $t->foreignId('teaching_group_id')->nullable()->constrained('teaching_groups')->restrictOnDelete();
            $t->foreignId('class_section_id')->nullable()->constrained('class_sections')->restrictOnDelete();
            $t->foreignId('teacher_id')->constrained('teachers')->restrictOnDelete();
            $t->foreignId('room_id')->constrained('rooms')->restrictOnDelete();
            $t->foreignId('bell_period_id')->constrained('bell_periods')->restrictOnDelete();
            $t->date('starts_on');
            $t->date('ends_on');
            $t->date('occurs_on')->nullable();
            $t->unsignedTinyInteger('weekday');
            $t->string('week_pattern', 8)->default('all');
            $t->boolean('active')->default(true);
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
            $t->softDeletes();
            $t->index(['school_id', 'session_year_id', 'weekday']);
        });
        Schema::table('timetables', function (Blueprint $t) {
            $t->foreignId('timetable_lesson_id')->nullable()->constrained('timetable_lessons')->restrictOnDelete();
            $t->foreignId('room_id')->nullable()->constrained('rooms')->restrictOnDelete();
            $t->string('room_name', 191)->nullable();
        });
    }

    public function down(): void
    {
        if (DB::table('timetable_lessons')->exists()) {
            throw new RuntimeException('Restore a tested backup to retain canonical timetable and register provenance.');
        }
        Schema::table('timetables', function (Blueprint $t) {
            $t->dropConstrainedForeignId('timetable_lesson_id');
            $t->dropConstrainedForeignId('room_id');
            $t->dropColumn('room_name');
        });
        foreach (['timetable_lessons', 'bell_periods', 'bell_schedules', 'rooms'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
