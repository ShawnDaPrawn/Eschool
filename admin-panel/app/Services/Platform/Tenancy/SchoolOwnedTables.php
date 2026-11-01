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
        'lurits_conflicts',
        'platform_incident_schools',
        'fee_tax_settings', 'fee_payment_choices', 'fee_reminder_policies', 'fee_reminders',
        'fee_accounting_settings',
        'bank_statement_imports', 'bank_statement_lines', 'bank_statement_import_rows', 'bank_statement_resolutions',
        'wallet_payment_intents', 'wallet_counter_payments', 'wallet_payment_closures', 'vendor_card_readers',
        'ticket_catalogue_revisions', 'ticket_events', 'ticket_types', 'tickets', 'ticket_actions', 'cash_handovers', 'cash_handover_outcomes', 'cashup_counts', 'cashup_count_sources', 'vendor_settlements', 'vendor_settlement_lines', 'vendor_settlement_approvals', 'vendor_settlement_outcomes', 'vendor_operators', 'vendor_operator_actions', 'vendor_order_counters', 'vendor_orders', 'vendor_order_lines', 'vendor_order_actions', 'vendor_stock_events',
        'vendor_categories', 'vendors', 'vendor_products', 'vendor_stock_balances', 'vendor_stock_movements', 'vendor_operations',
        'fee_accounts', 'fee_account_holders', 'fee_account_learners', 'bill_runs', 'invoices', 'invoice_lines', 'billing_operations', 'fee_items', 'fee_charges', 'fee_payer_corrections', 'fee_late_charge_policies', 'fee_late_policy_revisions', 'fee_late_charges', 'fee_item_eligibility_rules', 'fee_item_batches', 'fee_item_consents', 'fee_discounts', 'fee_discount_runs', 'fee_lifecycle_policies', 'fee_leaver_policies', 'fee_leaver_runs', 'wallet_settings', 'wallet_desk_policies', 'wallet_desk_topups', 'wallet_desk_actions', 'wallet_transfer_policies', 'wallet_transfers', 'wallet_deposit_policies', 'wallet_deposit_claims', 'wallet_parent_deposits', 'wallet_deposit_actions', 'wallet_withdrawal_policies', 'wallet_withdrawal_slots', 'wallet_withdrawal_slot_closures', 'wallet_withdrawals', 'wallet_withdrawal_actions', 'wallet_funding_policies', 'wallet_fundings', 'financial_receipts', 'financial_disbursements', 'wallet_accounts', 'wallet_limits', 'wallet_operations', 'fee_refund_policies', 'fee_refunds', 'fee_refund_actions', 'fee_credit_notes', 'fee_credit_releases', 'fee_payment_policies', 'payments', 'payment_allocation_runs', 'payment_allocations',
        'fee_plans', 'fee_plan_operations',
        'ledger_accounts', 'ledger_entries', 'ledger_postings',
        'foundation_classroom_groups', 'foundation_classroom_leases',
        'peer_tutoring_sessions', 'service_hours', 'peer_tutoring_operations',
        'activity_chat_policies', 'activity_chat_threads', 'activity_chat_messages', 'activity_chat_reads',
        'school_assistance_notice_intents',
        'school_knowledge_documents', 'school_support_threads', 'school_support_messages', 'school_assistance_operations', 'school_assistance_turns',
        'admission_requirements', 'admission_requirement_versions', 'pathway_operations',
        'teaching_drafts', 'teaching_draft_photos', 'teaching_draft_versions', 'teaching_draft_operations', 'task_insights',
        'diary_entries', 'diary_item_states',
        'tutor_sessions', 'tutor_messages', 'tutor_photos', 'tutor_checkpoints',
        'past_papers', 'past_paper_versions', 'exam_study_plans', 'exam_coach_operations', 'exam_answers', 'exam_answer_reviews',
        'ai_school_limits', 'ai_settings_operations',
        'ai_flag_reviews',
        'ai_quarantine_snapshots',
        'digest_language_templates', 'digest_preferences', 'weekly_parent_digests', 'digest_operations',
        'family_visit_rules', 'meeting_slots', 'gate_passes', 'family_visit_operations', 'family_visit_events',
        'consent_requests', 'consent_recipients', 'consent_responses', 'family_form_operations',
        'cards', 'card_keys', 'card_portraits', 'card_reader_devices', 'card_chip_profiles', 'card_qr_uses', 'card_scan_logs', 'card_classroom_sessions', 'card_events', 'card_operations',
        'gallery_settings', 'gallery_photos', 'photo_tags', 'gallery_events', 'gallery_operations', 'gallery_transfer_choices',
        'practice_items', 'practice_item_versions', 'practice_daily_sets', 'practice_set_entries', 'practice_memories', 'practice_reviews', 'practice_operations',
        'intakes', 'intake_grades', 'applications', 'application_documents', 'application_links', 'application_events', 'offers', 'admission_operations',
        'consultations', 'consultation_questions', 'consultation_options', 'consultation_candidates', 'consultation_participation', 'consultation_responses', 'consultation_configuration_events', 'consultation_operations',
        'discipline_operations', 'discipline_frameworks', 'discipline_framework_versions', 'discipline_records', 'discipline_record_versions',
        'discipline_acknowledgements', 'discipline_notice_intents', 'detention_sessions', 'detention_entries', 'detention_versions',
        'houses', 'house_memberships', 'point_categories', 'recognition_settings', 'house_point_limits', 'house_points', 'award_categories', 'award_category_admins', 'award_windows', 'award_definitions', 'award_nominations', 'award_nomination_versions', 'award_presentations', 'recognition_operations',
        'transfer_packages', 'transfer_consents', 'transfer_stages', 'transfer_receipts', 'transfer_operations',
        'learner_activity_days',
        'exam_periods', 'calendar_feeds',
        'risk_settings', 'risk_observations', 'risk_caseloads', 'interventions', 'intervention_versions', 'risk_operations',
        'wellbeing_notice_intents', 'incident_photos', 'wellbeing_categories', 'wellbeing_team_assignments', 'incident_reports', 'incident_threads', 'support_cases', 'support_case_assignments', 'support_case_entries', 'wellbeing_appointments', 'wellbeing_operations', 'wellbeing_events',
        'learner_note_operations', 'progress_band_states', 'progress_band_alerts', 'progress_band_settings', 'learner_notes', 'learner_note_revisions', 'learner_note_assignments',
        'support_assignments', 'support_plans', 'support_plan_versions', 'support_reviews', 'support_disclosures', 'support_operations',
        'cover_absences', 'cover_assignments', 'cover_operations',
        'mastery_settings', 'mastery_topics', 'topic_links', 'mastery_evidence', 'mastery_records', 'mastery_scopes',
        'activity_groups', 'activity_group_memberships', 'activity_group_staff', 'activity_sessions', 'activity_attendance', 'activity_materials', 'activity_material_files', 'activity_operations',
        'audiences', 'communication_role_rules', 'communication_operations', 'messages', 'message_recipients', 'message_reads',
        'content_operations', 'content_settings', 'content_items', 'content_sources', 'content_versions', 'content_item_groups', 'content_recipients', 'ai_usage', 'ai_flags',
        'academic_transition_policies', 'academic_transitions', 'academic_exit_packages', 'retention_rules',
        'report_templates', 'report_comment_banks', 'report_batches', 'report_cards', 'report_comments', 'report_recipients',
        'attendance_registers', 'attendance_entries', 'attendance_operations', 'attendance_notices',
        'rooms', 'bell_schedules', 'bell_periods', 'bell_schedule_overrides', 'timetable_lessons',
        'assessment_marks', 'question_marks', 'mark_snapshots', 'mark_releases', 'mark_release_entries', 'academic_exam_links',
        'teaching_groups', 'teaching_group_memberships', 'teaching_allocations', 'homeroom_allocations', 'academic_leaderships',
        'curriculum_plans', 'curriculum_topics', 'assessment_plans', 'assessment_tasks', 'assessment_questions',
        'profile_field_categories', 'profile_field_definitions', 'profile_field_values', 'profile_files', 'notification_dispatches', 'notification_rules', 'handle_proposals', 'school_modules', 'enrolments', 'guardian_links', 'learner_approvals', 'guardian_invitations', 'staff_invitations', 'school_accounts', 'school_contacts', 'school_contracts', 'school_account_events',
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
        'platform_ai_cost_grants' => 'explicit expiring cost-only operator grants; no school or learner-content access',
        'platform_incidents' => 'Navigate Ed\'s own breach and incident register, read and written only from /saas; the schools an incident affects are rows of platform_incident_schools',
        'platform_incident_entries' => 'the timeline of one platform incident, written only from /saas; names no school (the affected schools are in platform_incident_schools)',
        'platform_ai_caps' => 'Navigate Ed\'s own daily AI spending caps per feature, set only from /saas/ai; a school\'s own lower limits stay in ai_school_limits',
        'platform_ai_cap_changes' => 'the history of HQ AI cap changes (who, when, old and new cents, reason), written only from /saas/ai; names no school',
        'platform_admin_grants' => 'explicit expiring operator grants to the platform admin area; no school role, permission or record',
        'subject_standards' => 'public versioned CAPS/IEB catalogue; only the trusted platform operator importer writes, with no school HTTP writes',
        'grade_levels' => 'shared R to 12 grade and school-phase reference; no person or school records and no API writes',
        'academic_rating_levels' => 'shared seven-point percentage reference only; no learner marks or school records',
        'notification_preferences' => 'a human controls their own channel choices across schools; each schools mandatory rules override these choices',
        'handle_claims' => 'global unique handles and temporary reservations; locked by handle and accessed only through the identity service',
        'handle_changes' => 'one human identity across schools; change limit and history queried only by the authenticated or authorised learner user id',
        'learner_identifiers' => 'one immutable student number and the LURITS number per human across schools; accessed by user id under a lock',
        'user_devices' => 'push registrations belong to one human across schools, like users; each notification carries its own school',
        'platform_counters' => 'platform-wide sequence allocation under a row lock',
        'schools' => 'the schools themselves',
        'school_memberships' => 'the link between a person and a school: carries school_id (not null, foreign key) but is read across schools for one user',
        'users' => 'one row per human across schools; a user belongs to a school through school_memberships',
        'permissions' => 'one catalogue of permission names; roles hold them per school',
        'platform_staff_invitations' => 'one-use, expiring links for invited platform staff to choose a password; no school data',
        'platform_staff_identities' => 'global platform-operator exclusions; never school data or an access grant',
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
