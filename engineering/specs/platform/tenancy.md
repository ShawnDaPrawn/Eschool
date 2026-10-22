# Tenancy: schools, school_id, memberships and teams (Phase 1, feature 1.1)

Phase 1, work stream T1. Owner roles: Backend and Test. Source: the Build Bible, section 7 (build steps 1, 2 and 16), section 3.1 (Tenancy and Settings in the kernel) and section 5 (school isolation, data rules, the tenancy suite). Plan: [phase-1.md](../../phases/phase-1.md), "Contract between streams".

One install serves many schools. Every school-owned row carries the school it belongs to, every query on it is limited to the school the request works for, people belong to schools through memberships, and roles are granted per school. Installed apps keep working against school 1 through v1.

## Per-table decisions

Read from the real schema (`information_schema`) on the Phase 0 tip. The lists live in `App\Services\Platform\Tenancy\SchoolOwnedTables`, and `SchoolOwnedTablesTest` fails when a table is missing from them or its columns do not match its decision.

**School-owned, `school_id` NOT NULL** (indexed, foreign key to `schools`, back-filled to school 1, model uses `BelongsToSchool`):

| Area | Tables |
| --- | --- |
| People | `students`, `parents`, `teachers`, `staffs`, `student_sessions`, `student_subjects`, `learner_invitations` |
| Academic structure | `session_years`, `semesters`, `mediums`, `sections`, `streams`, `shifts`, `categories`, `classes`, `class_sections`, `class_session_configs`, `class_subjects`, `class_teachers`, `subject_teachers`, `subjects`, `elective_subject_groups`, `educational_programs`, `timetables`, `academic_calendars` |
| Teaching | `lessons`, `lesson_topics`, `assignments`, `assignment_submissions`, `files` |
| Attendance and leave | `attendances`, `staff_attendances`, `leaves`, `leave_details`, `leave_masters`, `holidays` |
| Exams | `exams`, `exam_classes`, `exam_timetables`, `exam_marks`, `exam_results`, `grades`, `online_exams`, `online_exam_questions`, `online_exam_question_answers`, `online_exam_question_choices`, `online_exam_question_options`, `online_exam_student_answers`, `student_online_exam_statuses` |
| Fees | `fees_types`, `fees_classes`, `fees_choiceables`, `fees_paids`, `installment_fees`, `paid_installment_fees`, `payment_transactions` |
| Communication | `announcements`, `notifications`, `user_notifications`, `chat_rooms`, `chat_members`, `chat_messages`, `chat_files`, `read_messages`, `email_tempalates` |
| Calendar, gallery and website | `events`, `multiple_events`, `medias`, `media_files`, `sliders`, `web_settings`, `faqs`, `contact_us` |
| Configuration | `form_fields` |

Child rows (`lesson_topics`, `chat_files`, `media_files`, `online_exam_question_options` and the like) carry `school_id` too, rather than following their parent: a child row looked up by its own id is then refused across schools by the same scope, with no join to remember.

**School-owned, `school_id` nullable** (indexed, no foreign key): `settings` (null rows are the platform's keys and the defaults beneath every school), `audit_log` and `domain_events` (platform actions and events have no school; log rows outlive what they describe), `notification_deliveries` (a delivery is logged for the school it was sent for), `idempotency_keys` (the school a write was made in; a key replayed in another school does not match).

**Spatie teams** (`school_id` is the team key): `roles` (each school has its own Super Admin, Teacher, Parent and Student roles and the roles it creates; nullable only because Spatie reserves null for a role every school shares, and none exists), `model_has_roles` and `model_has_permissions` (NOT NULL: a role or direct permission is given at one school).

**Join table following its parent**: `role_has_permissions` (a role's permissions belong to the role's school).

**Global**: `schools`; `school_memberships` (carries `school_id`, NOT NULL with a foreign key, but is read across schools for one user); `users` (one row per human; a user belongs to schools through memberships, so `users` has no `school_id`); `permissions` (one catalogue); `languages` (installed for the platform); `policy_acceptances` (a user accepts a platform policy once); `personal_access_tokens` (a token belongs to a user; the school is chosen on each request); `webhook_receipts` (provider event ids are unique per provider); and the framework's `sessions`, `password_resets`, `password_reset_tokens`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `migrations`.

## Migrations

| Migration | What it does | down() |
| --- | --- | --- |
| `2026_10_23_100000_create_schools_table` | `schools` (name, slug, status, timezone, package key, short name, tagline, crest path, theme and secondary colour, timestamps) and school 1 from the install's `school_name`, `school_short_name`, `school_tagline`, `logo1`, `time_zone`, `theme_color` and `secondary_color` settings | drops the table |
| `2026_10_23_100100_add_school_id_to_school_owned_tables` | a nullable `school_id` added last (instant on MySQL 8 and MariaDB), an index built online, and the foreign key added with foreign key checks off so it is added in place (every value is still null) | drops the foreign keys, indexes and columns it added |
| `2026_10_23_100200_backfill_school_id` | every row to school 1 in id ranges of 5000, only rows still empty; settings except the platform keys | sets the rows back to null |

`TenancyMigrationsTest` runs every down() and up() over a single-school install with data.
