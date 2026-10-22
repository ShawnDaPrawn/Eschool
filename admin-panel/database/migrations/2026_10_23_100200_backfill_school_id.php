<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Every existing row belongs to school 1, the school this install has run
 * so far (Build Bible, section 7, step 1).
 *
 * Rows are updated in id ranges of 5000, each its own statement, so no long
 * lock is held, and only rows whose school_id is still empty are touched:
 * running it again is harmless, and so is a row written between this and
 * the migration that makes the column required (that one back-fills again
 * first).
 *
 * Settings: the platform's own keys (mail server, push project, app
 * versions, the system version and one-off markers) stay with no school, as
 * the defaults beneath every school; every other row is school 1's.
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
        'audit_log', 'domain_events', 'idempotency_keys', 'notification_deliveries',
    ];

    /** Settings rows that stay the platform's (school_id null). */
    private const PLATFORM_SETTINGS = [
        'system_version', 'update_warning_modal', 'learner_dob_password_scan_completed_at',
        'mail_mailer', 'mail_host', 'mail_port', 'mail_username', 'mail_password', 'mail_encryption', 'mail_send_from',
        'email_configration_verification',
        'sender_id', 'project_id', 'service_account_file',
        'app_link', 'ios_app_link', 'app_version', 'ios_app_version', 'force_app_update', 'app_maintenance',
        'teacher_app_link', 'teacher_ios_app_link', 'teacher_app_version', 'teacher_ios_app_version', 'teacher_force_app_update', 'teacher_app_maintenance',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            $this->inRanges($table, fn (int $from, int $to) => DB::update(
                "UPDATE `{$table}` SET school_id = 1 WHERE id >= ? AND id < ? AND school_id IS NULL",
                [$from, $to]
            ));
        }

        $placeholders = implode(',', array_fill(0, count(self::PLATFORM_SETTINGS), '?'));
        $this->inRanges('settings', fn (int $from, int $to) => DB::update(
            "UPDATE settings SET school_id = 1 WHERE id >= ? AND id < ? AND school_id IS NULL AND type NOT IN ({$placeholders})",
            [$from, $to, ...self::PLATFORM_SETTINGS]
        ));
    }

    /** Back to no school, the way the rows were before (only school 1 exists until now). */
    public function down(): void
    {
        foreach ([...self::TABLES, 'settings'] as $table) {
            $this->inRanges($table, fn (int $from, int $to) => DB::update(
                "UPDATE `{$table}` SET school_id = NULL WHERE id >= ? AND id < ? AND school_id = 1",
                [$from, $to]
            ));
        }
    }

    /** @param  callable(int, int): mixed  $update */
    private function inRanges(string $table, callable $update): void
    {
        $min = DB::table($table)->min('id');
        $max = DB::table($table)->max('id');
        if ($min === null) {
            return;
        }
        for ($from = (int) $min; $from <= (int) $max; $from += self::CHUNK) {
            $update($from, $from + self::CHUNK);
        }
    }
};
