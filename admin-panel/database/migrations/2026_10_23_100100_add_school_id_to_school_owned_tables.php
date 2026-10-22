<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A nullable, indexed school_id on every school-owned table (Build Bible,
 * section 7, step 1; the decisions per table are in
 * engineering/specs/platform/tenancy.md). The next migration back-fills
 * every row to school 1 and a later one makes the column required.
 *
 * Safe on large tables: one ALTER TABLE per table adds the column last, its
 * index and its foreign key in a single online pass (concurrent reads and
 * writes go on), with foreign key checks off so the key is added in place
 * instead of by copying the table (every value is still null, so there is
 * nothing to check).
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
        Schema::withoutForeignKeyConstraints(function () {
            foreach (self::REQUIRED as $table) {
                $this->alter($table, in_array($table, self::HAD_COLUMN, true)
                    ? [$this->foreignKey($table)]
                    : ['ADD COLUMN `school_id` BIGINT UNSIGNED NULL', "ADD INDEX `{$table}_school_id_index` (`school_id`)", $this->foreignKey($table)]);
            }
            foreach (self::NULLABLE as $table) {
                $this->alter($table, ['ADD COLUMN `school_id` BIGINT UNSIGNED NULL', "ADD INDEX `{$table}_school_id_index` (`school_id`)"]);
            }
        });
    }

    public function down(): void
    {
        foreach (self::REQUIRED as $table) {
            $this->alter($table, in_array($table, self::HAD_COLUMN, true)
                ? ["DROP FOREIGN KEY `{$table}_school_id_foreign`"]
                : ["DROP FOREIGN KEY `{$table}_school_id_foreign`", "DROP INDEX `{$table}_school_id_index`", 'DROP COLUMN `school_id`']);
        }
        foreach (self::NULLABLE as $table) {
            $this->alter($table, ["DROP INDEX `{$table}_school_id_index`", 'DROP COLUMN `school_id`']);
        }
    }

    private function foreignKey(string $table): string
    {
        return "ADD CONSTRAINT `{$table}_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`)";
    }

    /**
     * One ALTER TABLE per table: the column, its index and its foreign key
     * in a single pass over the table.
     *
     * @param  list<string>  $changes
     */
    private function alter(string $table, array $changes): void
    {
        DB::statement("ALTER TABLE `{$table}` ".implode(', ', $changes));
    }
};
