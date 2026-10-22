<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * school_id becomes required on every table where a row always belongs to
 * a school (Build Bible, section 5.4), and the unique indexes that must now
 * hold per school gain it. Settings, the log tables and idempotency keys
 * stay nullable (see engineering/specs/platform/tenancy.md).
 *
 * Rows written since the back-fill (between the two deploy steps) are given
 * to school 1 first, in id ranges, so the change never fails on a null.
 * Changing a column to NOT NULL rebuilds the table in place; foreign key
 * checks are off meanwhile because the column is in a foreign key.
 *
 * down() puts the old single-school unique indexes back, so it works while
 * only school 1 has rows.
 */
return new class extends Migration
{
    private const CHUNK = 5000;

    private const TABLES = [
        'academic_calendars', 'announcements', 'assignment_submissions', 'assignments', 'attendances',
        'categories', 'chat_files', 'chat_members', 'chat_messages', 'chat_rooms',
        'class_sections', 'class_session_configs', 'class_subjects', 'class_teachers', 'classes',
        'contact_us', 'educational_programs', 'elective_subject_groups', 'email_tempalates', 'events',
        'exam_classes', 'exam_marks', 'exam_results', 'exam_timetables', 'exams',
        'faqs', 'fees_choiceables', 'fees_classes', 'fees_paids', 'fees_types',
        'files', 'form_fields', 'grades', 'holidays', 'installment_fees',
        'learner_invitations', 'leave_details', 'leave_masters', 'leaves', 'lesson_topics',
        'lessons', 'media_files', 'medias', 'mediums', 'multiple_events',
        'notifications', 'online_exam_question_answers', 'online_exam_question_choices', 'online_exam_question_options', 'online_exam_questions',
        'online_exam_student_answers', 'online_exams', 'paid_installment_fees', 'parents', 'payment_transactions',
        'read_messages', 'sections', 'semesters', 'session_years', 'shifts',
        'sliders', 'staff_attendances', 'staffs', 'streams', 'student_online_exam_statuses',
        'student_sessions', 'student_subjects', 'students', 'subject_teachers', 'subjects',
        'teachers', 'timetables', 'user_notifications', 'web_settings',
    ];

    /** Unique indexes that hold per school from now on: table => [old index, its columns]. */
    private const UNIQUE = [
        'email_tempalates' => ['email_tempalates_type_unique', ['type']],
        'staff_attendances' => ['staff_attendances_user_id_date_unique', ['user_id', 'date']],
    ];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            $min = DB::table($table)->whereNull('school_id')->min('id');
            $max = DB::table($table)->whereNull('school_id')->max('id');
            for ($from = (int) $min; $min !== null && $from <= (int) $max; $from += self::CHUNK) {
                DB::update("UPDATE `{$table}` SET school_id = 1 WHERE id >= ? AND id < ? AND school_id IS NULL", [$from, $from + self::CHUNK]);
            }
        }

        Schema::withoutForeignKeyConstraints(function () {
            foreach (self::TABLES as $table) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->unsignedBigInteger('school_id')->nullable(false)->change();
                });
            }
        });

        foreach (self::UNIQUE as $table => [$index, $columns]) {
            Schema::table($table, function (Blueprint $blueprint) use ($index, $columns) {
                $blueprint->unique(['school_id', ...$columns]);
                // The old index also served the foreign key on its first column.
                $blueprint->index([$columns[0]]);
                $blueprint->dropUnique($index);
            });
        }
    }

    public function down(): void
    {
        foreach (self::UNIQUE as $table => [$index, $columns]) {
            Schema::table($table, function (Blueprint $blueprint) use ($index, $columns) {
                $blueprint->unique($columns, $index);
                $blueprint->dropIndex([$columns[0]]);
                $blueprint->dropUnique(['school_id', ...$columns]);
            });
        }

        Schema::withoutForeignKeyConstraints(function () {
            foreach (self::TABLES as $table) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->unsignedBigInteger('school_id')->nullable()->change();
                });
            }
        });
    }
};
