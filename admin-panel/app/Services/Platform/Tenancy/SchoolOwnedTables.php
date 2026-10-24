<?php

declare(strict_types=1);

namespace App\Services\Platform\Tenancy;

/**
 * Every table in the database, sorted by how it belongs to a school (the
 * Build Bible, section 5.4: school_id on every school-owned table).
 * engineering/specs/platform/tenancy.md gives the reason for each decision,
 * and SchoolOwnedTablesTest checks these lists against the real schema, so a
 * new table fails the build until it is sorted here.
 *
 * The migrations keep frozen copies of these lists: changing a list here
 * never changes what an old migration does.
 */
final class SchoolOwnedTables
{
    /**
     * School data: school_id NOT NULL, indexed, a foreign key to schools,
     * and the model (where there is one) uses BelongsToSchool.
     */
    public const REQUIRED = [
        'profile_field_categories', 'profile_field_definitions', 'profile_field_values', 'profile_files', 'notification_dispatches', 'notification_rules', 'handle_proposals', 'school_modules', 'enrolments', 'guardian_links', 'learner_approvals', 'guardian_invitations',
        'term_templates', 'academic_calendars', 'announcements', 'assignment_submissions', 'assignments', 'attendances',
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
        'read_messages', 'sections', 'terms', 'session_years', 'shifts',
        'sliders', 'staff_attendances', 'staffs', 'streams', 'student_online_exam_statuses',
        'student_sessions', 'student_subjects', 'students', 'subject_teachers', 'subjects',
        'teachers', 'timetables', 'user_notifications', 'web_settings',
    ];

    /**
     * Rows that belong to a school when there is one and to the platform
     * when there is not: school_id nullable and indexed, no foreign key (log
     * rows outlive what they describe). Settings rows with no school are the
     * platform defaults beneath every school's own.
     */
    public const NULLABLE = [
        'audit_log', 'domain_events', 'idempotency_keys', 'notification_deliveries', 'settings',
    ];

    /**
     * Spatie permission's teams mode, keyed on school_id (config/permission.php):
     * a role belongs to one school (null would be a role every school shares;
     * none exists), and every role or permission given to a user is given at
     * one school.
     */
    public const TEAMS = [
        'roles', 'model_has_roles', 'model_has_permissions',
    ];

    /** Join tables whose rows belong to the school of their parent row. */
    public const FOLLOWS_PARENT = [
        'role_has_permissions' => 'roles',
    ];

    /** Tables that belong to no one school, each with the reason. */
    public const GLOBAL = [
        'grade_levels' => 'shared R to 12 grade and school-phase reference; no person or school records and no API writes',
        'academic_rating_levels' => 'shared seven-point percentage reference only; no learner marks or school records',
        'notification_preferences' => 'a human controls their own channel choices across schools; each schools mandatory rules override these choices',
        'handle_claims' => 'global unique handles and temporary reservations; locked by handle and accessed only through the identity service',
        'handle_changes' => 'one human identity across schools; change limit and history queried only by the authenticated or authorised learner user id',
        'learner_identifiers' => 'one immutable student number per human across schools; accessed by user id under a lock',
        'platform_counters' => 'platform-wide sequence allocation under a row lock',
        'schools' => 'the schools themselves',
        'school_memberships' => 'the link between a person and a school: carries school_id (not null, foreign key) but is read across schools for one user',
        'users' => 'one row per human across schools; a user belongs to a school through school_memberships',
        'permissions' => 'one catalogue of permission names; roles hold them per school',
        'languages' => 'interface languages are installed for the whole platform',
        'policy_acceptances' => 'a user accepts a platform policy version once',
        'personal_access_tokens' => 'belong to a user; the school comes from the membership on each request',
        'webhook_receipts' => 'provider event ids are unique per provider; the payment a webhook settles is found explicitly',
        'sessions' => 'framework: web sessions',
        'password_resets' => 'framework: password reset tokens (users are global)',
        'password_reset_tokens' => 'framework: password reset tokens (users are global)',
        'cache' => 'framework: cache store',
        'cache_locks' => 'framework: cache locks',
        'jobs' => 'framework: queue; each job carries its school in its context',
        'job_batches' => 'framework: queue batches',
        'failed_jobs' => 'framework: failed queue jobs',
        'migrations' => 'framework: migration log',
    ];

    /** Tables that carry a school_id column. */
    public static function withSchoolId(): array
    {
        return [...self::REQUIRED, ...self::NULLABLE, ...self::TEAMS];
    }

    /**
     * Whether rows of this table belong to a school: validation rules and
     * SchoolTable limit them to the current school.
     */
    public static function isSchoolOwned(string $table): bool
    {
        return in_array($table, self::REQUIRED, true) || in_array($table, self::NULLABLE, true) || $table === 'roles';
    }

    private function __construct() {}
}
