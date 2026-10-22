<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A nullable, indexed school_id on every school-owned table (Build Bible,
 * section 7, step 1; the decisions per table are in
 * engineering/specs/platform/tenancy.md). The next migration back-fills
 * every row to school 1 and a later one makes the column required.
 *
 * Safe on large tables: the column is added last (an instant change on
 * MySQL 8 and MariaDB 10.3 and later), the index is built online, and the
 * foreign key is added with foreign key checks off so MySQL adds it in
 * place instead of copying the table (every value is still null, so there
 * is nothing to check).
 *
 * Frozen lists: App\Services\Platform\Tenancy\SchoolOwnedTables is today's
 * classification; this migration keeps the one it was written with.
 */
return new class extends Migration
{
    /** School data: the column, its index and a foreign key to schools. */
    private const REQUIRED = [
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

    /**
     * Tables that already had a nullable, indexed school_id from Phase 0:
     * learner_invitations gains its foreign key here; the log tables and
     * settings keep a plain column.
     */
    private const HAD_COLUMN = ['learner_invitations', 'audit_log', 'domain_events', 'notification_deliveries', 'settings'];

    /** Nullable and indexed, without a foreign key. */
    private const NULLABLE = ['idempotency_keys'];

    public function up(): void
    {
        foreach ([...self::REQUIRED, ...self::NULLABLE] as $table) {
            if (in_array($table, self::HAD_COLUMN, true) || Schema::hasColumn($table, 'school_id')) {
                continue;
            }
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->unsignedBigInteger('school_id')->nullable();
            });
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->index('school_id');
            });
        }

        Schema::withoutForeignKeyConstraints(function () {
            foreach (self::REQUIRED as $table) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->foreign('school_id')->references('id')->on('schools');
                });
            }
        });
    }

    public function down(): void
    {
        foreach (self::REQUIRED as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropForeign(['school_id']);
            });
        }

        foreach ([...self::REQUIRED, ...self::NULLABLE] as $table) {
            if (in_array($table, self::HAD_COLUMN, true)) {
                continue;
            }
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropIndex(['school_id']);
                $blueprint->dropColumn('school_id');
            });
        }
    }
};
