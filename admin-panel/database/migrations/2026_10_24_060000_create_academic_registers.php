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
        Schema::create('attendance_registers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('session_year_id')->constrained('session_years')->restrictOnDelete();
            $t->foreignId('class_section_id')->nullable()->constrained('class_sections')->restrictOnDelete();
            $t->foreignId('timetable_lesson_id')->nullable()->constrained('timetable_lessons')->restrictOnDelete();
            $t->foreignId('grade_level_id')->constrained('grade_levels')->restrictOnDelete();
            $t->date('on_date');
            $t->string('scope_key', 64);
            $t->string('label', 255);
            $t->unsignedInteger('revision')->default(0);
            $t->timestamps();
            $t->unique(['school_id', 'session_year_id', 'on_date', 'scope_key'], 'attendance_register_scope');
        });
        Schema::create('attendance_entries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('attendance_register_id')->constrained('attendance_registers')->restrictOnDelete();
            $t->foreignId('learner_id')->constrained('students')->restrictOnDelete();
            $t->string('state', 16);
            $t->string('source', 16);
            $t->unsignedInteger('revision')->default(1);
            $t->foreignId('captured_by')->constrained('users')->restrictOnDelete();
            $t->foreignId('amended_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestamp('amended_at')->nullable();
            $t->text('reason')->nullable();
            $t->timestamps();
            $t->unique(['school_id', 'attendance_register_id', 'learner_id'], 'attendance_entry_learner');
        });
        Schema::create('attendance_operations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('attendance_register_id')->constrained('attendance_registers')->restrictOnDelete();
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->uuid('operation_id');
            $t->char('payload_hash', 64);
            $t->json('result');
            $t->timestamps();
            $t->unique(['school_id', 'actor_id', 'operation_id'], 'attendance_operation_identity');
        });
        Schema::create('attendance_notices', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('attendance_entry_id')->constrained('attendance_entries')->restrictOnDelete();
            $t->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $t->foreignId('notification_id')->nullable()->constrained('notifications')->restrictOnDelete();
            $t->string('kind', 16);
            $t->unsignedInteger('revision');
            $t->timestamps();
            $t->unique(['school_id', 'attendance_entry_id', 'user_id', 'kind', 'revision'], 'attendance_notice_once');
        });
        Schema::table('attendances', function (Blueprint $t) {
            $t->foreignId('attendance_entry_id')->nullable()->constrained('attendance_entries')->restrictOnDelete();
            $t->foreignId('amended_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestamp('amended_at')->nullable();
            $t->string('source', 16)->nullable();
        });
        Schema::table('students', fn (Blueprint $t) => $t->unsignedInteger('attendance_qr_version')->default(1));
    }

    public function down(): void
    {
        if (DB::table('attendance_entries')->exists()) {
            throw new RuntimeException('Restore a tested backup to retain register amendments and offline operation provenance.');
        }
        Schema::table('students', fn (Blueprint $t) => $t->dropColumn('attendance_qr_version'));
        Schema::table('attendances', function (Blueprint $t) {
            $t->dropConstrainedForeignId('attendance_entry_id');
            $t->dropConstrainedForeignId('amended_by');
            $t->dropColumn(['amended_at', 'source']);
        });
        foreach (['attendance_notices', 'attendance_operations', 'attendance_entries', 'attendance_registers'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
