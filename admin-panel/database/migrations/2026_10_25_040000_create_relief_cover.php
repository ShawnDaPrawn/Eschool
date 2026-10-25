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
        Schema::create('cover_absences', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('session_year_id')->constrained('session_years');
            $t->foreignId('teacher_id')->constrained('teachers');
            $t->foreignId('requested_by')->constrained('users');
            $t->date('starts_on');
            $t->date('ends_on');
            $t->text('reason')->nullable();
            $t->text('cancel_reason')->nullable();
            $t->string('state', 24)->default('requested');
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
            $t->index(['school_id', 'teacher_id', 'starts_on', 'ends_on'], 'cover_absence_window');
        });
        Schema::create('cover_assignments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('cover_absence_id')->constrained('cover_absences');
            $t->foreignId('timetable_lesson_id')->constrained('timetable_lessons');
            $t->date('on_date');
            $t->char('source_hash', 64);
            $t->longText('source_snapshot');
            $t->foreignId('substitute_teacher_id')->nullable()->constrained('teachers');
            $t->string('state', 24)->default('open');
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
            $t->unique(['cover_absence_id', 'timetable_lesson_id', 'on_date'], 'cover_occurrence_identity');
            $t->index(['school_id', 'substitute_teacher_id', 'on_date'], 'cover_substitute_day');
        });
        Schema::create('cover_operations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('user_id')->constrained('users');
            $t->uuid('operation_id');
            $t->char('request_hash', 64);
            $t->string('result_kind', 24);
            $t->unsignedBigInteger('result_id');
            $t->timestamps();
            $t->unique(['school_id', 'operation_id']);
        });
    }

    public function down(): void
    {
        foreach (['cover_operations', 'cover_assignments', 'cover_absences'] as $table) {
            if (DB::table($table)->exists()) {
                throw new RuntimeException('Retained cover history prevents rollback.');
            }
        }
        foreach (['cover_operations', 'cover_assignments', 'cover_absences'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
