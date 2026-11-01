# Tenancy: schools, school_id, memberships and teams (Phase 1, feature 1.1)

Phase 1, work stream T1. Owner roles: Backend and Test. Source: the Build Bible, section 7 (build steps 1, 2, 8 and 16), section 3.1 (Tenancy and Settings in the kernel) and section 5 (school isolation, data rules, the tenancy suite). Plan: [phase-1.md](../../phases/phase-1.md), "Contract between streams".

One install serves many schools. Every school-owned row carries the school it belongs to, every query on it is limited to the school the request works for, people belong to schools through memberships, and roles are granted per school. Installed apps keep working against school 1 through v1.

## User stories

- **School admin (one school):** I see and change only my school's learners, staff, classes, fees, notices, settings and roles; another school's records cannot be listed, opened, exported or found in search, even by typing its ids.
- **School admin at two schools:** I pick the school in the panel header next to the year switcher; every page, list and export follows it.
- **Teacher, learner, parent (apps):** my app keeps working as before (v1, school 1); a newer app sends `X-School-Id` to choose among my schools, and `/me/context` lists my schools with my roles at each.
- **Parent with a child at each school:** I reach each child while working in that child's school; one school's child is never served while working in the other.
- **SaaS admin (stream T4):** I have no membership; I reach a school only through audited impersonation, which the tenancy model allows (`CurrentSchool::impersonate()`).
- **Engineer:** I add `school_id` and `BelongsToSchool` to a new school-owned table and model, and the scope, the validation rules, the queue and the tests take care of the rest.

## Acceptance tests

| Given | When | Then | Test |
| --- | --- | --- | --- |
| A single-school install with settings and data | the migrations run | school 1 is created from its name, crest and timezone; every row is school 1's; platform keys stay without a school; each down() undoes its up() | `TenancyMigrationsTest` |
| The database | inspected | every table is sorted; school-owned tables have a NOT NULL, indexed `school_id` with a foreign key; every model of one uses `BelongsToSchool` | `SchoolOwnedTablesTest` |
| Rows of two schools | a model is queried in school 1 | only school 1's rows; new rows, bulk inserts and upserts get the school's id; `acrossSchools()` sees both | `SchoolScopeTest` |
| Console work without a school | it reads, then writes a school row | the read sees every school; the write throws `NoCurrentSchool` | `SchoolScopeTest::test_console_work_without_a_school...` |
| A signed-in route without `school.context` | it reads school data | refused with `NoCurrentSchool` (500), never another school's rows; a public route uses the default school | `SchoolScopeTest::test_a_signed_in_request_without_a_school...` |
| A job queued in school 2 | the worker runs it | it runs in school 2 | `SchoolScopeTest::test_a_queued_job_runs_in_the_school_it_was_queued_in` |
| `exists:` and `unique:` rules | validated in a school | another school's record does not exist; another school may reuse a name | `SchoolScopeTest::test_exists_and_unique_rules_hold_within_the_school` |
| Settings of two schools and the platform | read and written | the school's rows over the platform's; platform keys shared; the schools row follows the name | `SchoolScopeTest::test_settings_are_per_school_over_the_platforms` |
| Users of two schools | queried in school 1 | another school's people are not found; accounts with no school yet are | `SchoolScopeTest::test_users_of_another_school_are_not_found` |
| A user with memberships | `X-School-Id` names a school they are (not) an active member of | that school (403); no header: their first school; no active membership: 403, the panel signs them out | `SchoolContextTest` |
| Role assigned or removed | at a school | the membership is granted or ended there | `SchoolContextTest::test_assigning_a_role_grants_the_membership...` |
| Teams mode | a role given at school 2 | it counts only at school 2; the Roles page lists and opens only the school's roles; a new school gets its system roles | `SchoolRolesTest` |
| A school-wide push | sent from school 2 / school 1 | `school_2_*` topics only / `school_1_*` plus the legacy names | `QueuedDeliveryTest` (topic tests) |
| An admin at two schools | switches school in the header | lists follow the school; a school they are not at is refused; a stale choice falls back | `PanelSchoolSwitcherTest` |
| `/me/context` | for a user at two schools, with and without `X-School-Id` | the chosen school, `user.roles` there, every school with its roles; a later contributor adds fields without removing any | `ContextSchoolsTest`, `ContextTest`, `OpenApiContractTest` |
| Every API route (v1 and v2) | a School A user asks for School B's records (every id School B's; for parent and teacher routes also with their own child or learner) | nothing of School B in the answer, no server error; v2 with `X-School-Id` of School B: 403 | `ApiRouteTenancyTest` (one case per route) |
| Every panel page, list, page with an id, export, document and search | School A's admin opens it with School B's ids | nothing of School B in the page or in the data handed to a PDF's view, no server error | `PanelTenancyTest` |
| The demo seeder | run | two schools, a user per profile at each, a parent with a child at each, an admin at both | `DemoSchoolSeederTest` |

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
| Configuration | `form_fields`, `profile_field_categories`, `profile_field_definitions`, `profile_field_values`, `profile_files` |

Child rows (`lesson_topics`, `chat_files`, `media_files`, `online_exam_question_options` and the like) carry `school_id` too, rather than following their parent: a child row looked up by its own id is then refused across schools by the same scope, with no join to remember. `learner_invitations` is the one model without the scope: an invitation is found by its secret token or code on the public set-up page, before anyone knows the school, and is always created with the school set.

**School-owned, `school_id` nullable** (indexed, no foreign key): `settings` (null rows are the platform's keys and the defaults beneath every school), `audit_log` and `domain_events` (platform actions and events have no school; log rows outlive what they describe), `notification_deliveries` (a delivery records the school it was sent for), `idempotency_keys` (the school a write was made in).

**Spatie teams** (`school_id` is the team key): `roles` (each school has its own Super Admin, Teacher, Parent and Student roles and the roles it creates; nullable only because Spatie reserves null for a role every school shares, and none exists), `model_has_roles` and `model_has_permissions` (NOT NULL and part of the primary key: a role or direct permission is given at one school).

**Join table following its parent**: `role_has_permissions` (a role's permissions belong to the role's school).

**Global**: `schools`; `school_memberships` (carries `school_id`, NOT NULL with a foreign key, but is read across schools for one user); `users` (one row per human; a user belongs to schools through memberships, so `users` has no `school_id`, and `MemberScope` limits user queries instead); `permissions` (one catalogue); `languages` (installed for the platform); `policy_acceptances` (a user accepts a platform policy once); `personal_access_tokens` (a token belongs to a user; the school is chosen on each request); `webhook_receipts` (provider event ids are unique per provider); and the framework's `sessions`, `password_resets`, `password_reset_tokens`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `migrations`.

**Unique indexes made per school**: `email_tempalates (school_id, type)` and `staff_attendances (school_id, user_id, date)`. The app's own uniqueness rules (`unique:session_years,name`, admission numbers, subject codes, form field names, role names and so on) hold per school through the validation presence verifier, so no other index needed widening. `users.email` stays unique across the platform (one row per human).

## Data changes

| Migration | What it does | down() |
| --- | --- | --- |
| `2026_10_23_100000_create_schools_table` | `schools` (name, slug, status, timezone, package key, short name, tagline, crest path, theme and secondary colour, timestamps) and school 1 from the install's `school_name`, `school_short_name`, `school_tagline`, `logo1`, `time_zone`, `theme_color` and `secondary_color` settings | drops the table |
| `2026_10_23_100100_add_school_id_to_school_owned_tables` | per table one ALTER TABLE: a nullable `school_id` added last, its index and its foreign key, in one online pass, with foreign key checks off so the key is added in place (every value is still null) | drops the foreign keys, indexes and columns it added |
| `2026_10_23_100200_backfill_school_id` | every row to school 1 in id ranges of 5000, only rows still empty; settings except the platform keys | sets the rows back to null |
| `2026_10_23_100300_create_school_memberships_table` | `school_memberships` (user, school, role, status, starts_on, ends_on; unique user, school and role) from every role grant, at school 1, from the account's creation date; soft-deleted users' memberships are ended | drops the table |
| `2026_10_23_100400_require_school_id` | back-fills stragglers, then NOT NULL on every school-owned table; the two unique indexes above gain `school_id` | nullable again; the old indexes back (works while only school 1 has rows) |
| `2026_10_23_100500_add_school_id_to_permission_tables` | teams mode: `roles.school_id` (school 1, unique per school by name and guard), `model_has_roles` and `model_has_permissions` with `school_id` in the primary key, foreign keys to `schools` | back to one set of roles (works while only school 1 has roles) |

The old `2022_04_01_091033_create_permission_tables` now always creates the single-school shape, whatever `config('permission.teams')` says, so a new install and an upgraded one reach teams mode through the same migration. `TenancyMigrationsTest` runs every down() and up() over a single-school install with data. Deployers: run the migrations, then `php artisan permission:cache-reset`.

## How a request finds its school

`App\Services\Platform\Tenancy\CurrentSchool` (one per request or job) holds the school; `BelongsToSchool` (global `SchoolScope`, fill on create, `SchoolBuilder` for bulk inserts) limits every school-owned model to it; Spatie's team, the log context (`school_id` on every JSON log line) and the settings applied to config (school name, timezone) follow it.

| Where | The school | Without one |
| --- | --- | --- |
| Signed-in panel and API routes (`school.context`, after auth, before route model binding) | one of the user's active memberships: `X-School-Id` (API) or the switcher's session choice (panel), else the first; a school they are not an active member of is 403 | no active membership: 403, or signed out of the panel; platform staff only through `impersonate()` |
| Public routes (`school.context:public`) | the signed-in user's school when a session or token comes along, else a school `X-School-Id` names, else `config('tenancy.default_school_id')` (school 1) | never refuses |
| Sign-in and password reset (`school.context:sign-in`) | the school of the account the request names (email or GR number) | as public |
| Webhooks | public first; the Stripe webhook then runs in the school of the payment it settles | — |
| Console commands and seeders | `CurrentSchool::run($school, fn () => ...)`; the installer's seeders run for every school | reads see every school; a school-owned write throws `NoCurrentSchool` |
| Queued jobs | the school they were queued in (Laravel's Context carries `school_id`) | none |
| Platform code across schools | `CurrentSchool::acrossSchools(fn () => ...)` or `Model::acrossSchools()` | — |
| Migrations | across schools (old migrations use today's models on tables without `school_id`) | — |

A signed-in request on a route without `school.context` is refused the moment it asks for school data (`NoCurrentSchool`), so isolation never fails open. Users have no `school_id`: once a school is chosen, `MemberScope` limits user queries to its members and to accounts that belong to no school yet.

`exists:` and `unique:` validation rules on school-owned tables (and `roles`) see only the current school's rows (`SchoolAwarePresenceVerifier`). `SchoolTable::query('table')` is `DB::table()` with the same scope and fill, for code without models.

## Memberships and roles

- `school_memberships`: one row per user, school and role, status `active`, `suspended` or `ended`, with `starts_on` and `ends_on`. Active means status active, today within the dates, and the school active. `MembershipService` reads them (one query, schools joined, cached for the request).
- Spatie runs in teams mode keyed on `school_id`. `assignRole`, `syncRoles` and `removeRole` act at the current school; memberships follow them (Spatie's role events, `PlatformServiceProvider`). `App\Models\Platform\Role` limits role queries to the current school; `Permission::roles()` reads every school's, because Spatie's shared permission cache is built from it.
- `SchoolRoles::seed($school)` creates or updates a school's Super Admin, Teacher, Parent and Student roles with their permissions; `InstallationSeeder` runs it for every school on each deploy, and seeds ID card defaults and email templates per school (`EmailSeeder`).

## Settings and pushes

- `getSettings()` and `schoolSetting()` read the current school's rows over the platform's (`school_id` null). The `Settings` model sees the school's rows plus the platform keys; a new row is the school's, or the platform's for a platform key (`PlatformSettings::KEYS`: mail server, push project, app versions, the system version, one-off markers). The schools row follows a school's name, crest, timezone and colours (`SchoolBranding`).
- The pages that change what every school shares (mail server, push project, app versions, languages) open only in the default school (`school.default`) until the platform admin area takes them over.
- School-wide pushes go to `school_{id}_{audience}_{platform}` (for example `school_2_parents_ios`); school 1 also keeps the pre-Phase 1 names (`allAndroid`, `parentsIOS` ...) so installed apps keep receiving its notices. Those names can be dropped once the apps that subscribe to the per-school topics are the minimum version the API forces (`force_app_update`) and the request logs show no older version for a full term (`PushTopics`).

## Endpoints

- `GET /api/v2/me/context` (`auth:sanctum`, `school.context`, `role.any`): composed by the contributors in `config/context.php`. T1's: `user` (with `roles` at the current school), `schools` (`[{school: SchoolSummary, roles}]`), `current_school` (from the school's settings). Placeholders keep Phase 0's `children`, `sign_in_methods` (stream T2 replaces them) and `modules`, `notification_rules`, `feature_flags` (stream T3). A later contributor's object is merged into an earlier one's, so a stream may add fields inside `current_school` but never removes one. `X-School-Id` is documented in `openapi.yaml` (`components/parameters/SchoolId`); `packages/eschool_api` is regenerated.
- `POST /school/switch` (panel; `SchoolSwitchController`, `SwitchSchoolRequest`): the school switcher.
- Every v1 and v2 group: `school.context` after `auth:sanctum` (`school.context:optional` on `logout` and `change-password`); public v1 routes `school.context:public`, sign-in and password reset `school.context:sign-in`. v1 answers keep their exact shape (`school_id` is hidden on every model and role).

## Screens

- Panel header: a Material 3 school field beside the year switcher, only for people with more than one active school; on small screens it sits at the top of the navigation drawer. Choosing a school reloads the panel in it (Home, with a confirmation) and resets the selected year to that school's current one.

## Notification types

None new. Topic names change as above.

## Module switch

None: tenancy is always on.

## Security notes

- The current school comes from the user's memberships, never from a request parameter on its own; `X-School-Id` only picks among them.
- Isolation is enforced in layers: the model scope and fill, the user member scope, the validation presence verifier, Spatie teams, per-school settings, and per-school push topics. The tenancy suite (`ApiRouteTenancyTest`, `PanelTenancyTest`) checks every API route and every panel page, list, export, document and search with a second school's ids.
- Raw `DB::table()` reads bypass the scope: new code uses models or `SchoolTable`; the legacy raw queries left filter by a school-owned id (a session year, a homeroom) and were reviewed.
- A refusal: v2 answers 403 or 404; v1 keeps its existing error answers for a record it cannot find (an error envelope, a 4xx or an empty list) so installed apps see no new error codes; the suite checks that no answer carries another school's data and none is a server error.
- Platform staff have no membership; stream T4 adds audited impersonation on top of `CurrentSchool::impersonate()` and a cross-school mode for `/saas` (`allowAcrossSchools()`).

## Self-test steps

1. `php artisan migrate:fresh --seed && php artisan db:seed --class=DemoSchoolSeeder`.
2. Sign in as `admin.a@demo.navigateed.test`: no school switcher; learners, fees and search show only NavigateEd Demo School's records.
3. Sign in as `admin.b@...`: only Baobab Demo High's (Grade 8 and 9, Geography, Boarding fees).
4. Sign in as `admin.both@...`: the switcher lists both schools; switch and check that the lists, the year and the school name follow.
5. With a token for `parent.both@...`, call `/api/v2/me/context` with and without `X-School-Id: <Baobab's id>`: two schools; each child only in its own school.
6. Send a notice to all from each school and check the topics in `notification_deliveries`.

## Phase 2 reference tables

`grade_levels` and `academic_rating_levels` are deliberately global, read-only
reference data with no school or person values. Their global classification is
registered in SchoolOwnedTables. The nullable `classes.grade_level_id` points to
that shared catalogue; classes remain school-owned and every mapping lookup and
write is scoped to CurrentSchool. No class or learner data is stored globally.
## Platform staff exclusion registry (Phases 3 and 4)

`platform_staff_identities` is global: it records which platform operators must
be denied restricted school counselling/safeguarding records, including when
they also hold school memberships. It has no school_id and grants no school or
platform permission. Trusted console changes are audited; no HTTP registry
management is exposed. See `restricted-pastoral-access.md` for the full contract.

## Principal dashboard activity observations

`learner_activity_days` is school-owned and stores one learner/grade/year/day receipt per school. It contains no request path, clinical text or token metadata. Successful API use records only a currently approved, active, dated enrolled learner in the selected school. The daily retention command removes observations older than ninety days across each school context. Principal aggregates never consult global token `last_used_at`, which cannot identify the school of use.

## Family consent records

`consent_requests`, `consent_recipients`, `consent_responses` and
`family_form_operations` are school-owned. Composite school/request, school/group
and school/learner foreign keys prevent cross-school reference substitution.
Wording, signatures' typed names and operation receipts are encrypted; immutable
request fingerprints and response attestations bind the exact audience and text.
Existing encrypted profile values remain the only health-value store.

## Private teaching drafts and insights

`teaching_drafts`, `teaching_draft_versions`, `teaching_draft_operations`,
`teaching_draft_photos` and `task_insights` are required school-owned tables.
Their models use `BelongsToSchool`; school foreign keys and indexes are checked
by the inventory suite. Draft-version links include the school in their foreign
key. Encrypted contexts, definitions, crop metadata and aggregate snapshots stay
inside this boundary; operation IDs and private filenames do not grant access.

Tutor topic/checkpoint follow-on: `tutor_checkpoints` is school-owned and indexed
by school, owner and session. Only its owning approved learner may read/answer a
checkpoint; teacher-approved curriculum definitions are resolved after fresh
current-group/source checks. `tutor_sessions.topic_id` remains nullable for older
subject-only sessions. School-scoped immutable issue/answer receipts and verified
practice-provenance evidence are retained, without official mark writes.

## Parent meetings and early collection

`family_visit_rules`, `meeting_slots`, `gate_passes`,
`family_visit_operations` and `family_visit_events` require an indexed,
non-null school foreign key and scoped models. Learner, reader and scan
references additionally use composite school foreign keys. Encrypted event
snapshots and exact-operation receipts retain appointment and collection
history. Platform staff and impersonation are excluded; only fresh verified
guardians, scoped meeting hosts, explicitly authorised school staff and
purpose-bound online gate readers can enter their respective workflows.
# School assistance records

`school_knowledge_documents`, `school_support_threads`,
`school_support_messages` and `school_assistance_operations` are school-owned
with required indexed school foreign keys and model tenant scopes. Approved
documents are immutable per version. A private support message is read only
after fresh Parent ownership or explicit office assignment; the unclaimed
office queue contains opaque metadata. Sent text and exact-operation receipts
are encrypted. Expired threads are refused before body retrieval, and the
bounded tenant purge removes message bodies while retaining minimal receipts.

## Pathway references

`admission_requirements`, `admission_requirement_versions` and `pathway_operations` are school-owned, with required indexed foreign keys and scoped models. A composite current-version foreign key also binds a revision to its own source in the same school. Published source revisions and maintaining staff identities remain internal to that school's catalogue management. Private learner projections are transient and introduce no stored cross-school reference or new academic mark table.

Peer tutoring adds school-scoped `peer_tutoring_sessions`, append-only `service_hours` and reference-only `peer_tutoring_operations`. All models use the existing school boundary.

The learner diary adds school-owned `diary_entries` and `diary_item_states`, each bound to one learner's `user_id`. Text is encrypted with a key derived per school and learner, so a row copied into another school's context cannot be read there either. School items are computed per read from the current school's own homework, holidays and events; nothing is copied across schools.

Foundation classroom eligibility and device/Teacher delegations (`foundation_classroom_groups`, `foundation_classroom_leases`) are school-owned. Existing classroom learner sessions additionally reference a same-school delegation, never a gate scan.

### Ledger preparation

`ledger_accounts`, `ledger_entries` and `ledger_postings` are required school-owned
tables. The internal service always supplies an explicit active school; composite
foreign keys prevent cross-school account/entry postings even through raw SQL.
There is no public ledger write API. See the [ledger contract](../money/ledger-foundation.md).


Bank statement imports, lines, import-row links and resolutions are required
school-owned tables (`bank_statement_imports`, `bank_statement_lines`,
`bank_statement_import_rows`, `bank_statement_resolutions`). All carry required
indexed school foreign keys; their cross-document links are composite school/ID
foreign keys. The three Eloquent models use BelongsToSchool; the link table is
queried with an explicit current-school predicate. Bank source uniqueness and
receipt matching are per school, with no platform-wide financial read endpoint.

`fee_accounting_settings` is required school-owned, with a unique school foreign
key. Its Eloquent model uses BelongsToSchool. Reports and CSV exports use current-
school predicates on every raw join, including invoice shares and ledger postings.

### Identity: devices and LURITS

`user_devices` is global, like `users`: one push registration per device of one
human, each notification carrying its own school ([push devices](push-devices.md)).
LURITS numbers live on the global `learner_identifiers` row of the human.
`lurits_conflicts` is required school-owned: the claiming school sees only its own
open claims, and the holder school is never told of them ([LURITS](lurits.md)).

### HQ AI spending caps

`platform_ai_caps` and `platform_ai_cap_changes` are global: Navigate Ed's own
daily AI caps per feature and their history, read and written only from
`/saas/ai`. They name no school. A school's own lower limits stay in the
school-owned `ai_school_limits`, which HQ never writes ([HQ AI](hq-ai.md)).

### HQ incident register

`platform_incidents` and `platform_incident_entries` are global: Navigate Ed's
own register, read and written only from `/saas`. `platform_incident_schools` is
required school-owned (a foreign key to the school) so each affected school, and
when it was told, is one row per school; HQ reads it across schools and no school
reads it ([HQ incidents](hq-incidents.md)).

### A guardian at two schools (Action Plan 1.1, server side)

A parent with children at two schools signs in once. `/api/v2/me/context` lists both schools and each child with their own school. The app reads each child with `X-School-Id` set to that child's school: the header picks one of the user's active memberships, and a school they don't belong to is refused (403).

A child is only found in their own school. Every v2 family read keyed by `{learner}` refuses another school's child, with or without the header, and never echoes that child's details. The other school's child is not found (404) or the read is refused (403/422), never answered.

`Tenancy/ParentAcrossSchoolsTest` checks this:
- It finds the family reads from the route list rather than a fixed list, so a new `learners/{learner}` read is covered automatically.
- Five reads the demo schools have data for (content, marks periods, register QR, register today, reports) are named controls. Each must answer 200 for each child in that child's own school and refuse the other school's child, so for these a refusal is the school check and not a read that refuses every parent. The other reads in the sweep need module data the demo schools don't have; for them, the sweep proves only that they never answer across schools.

The apps' parent home and child screens are app work and not part of this step; nor are the v1 parent endpoints that take `child_id`.

### Calendar feeds

`calendar_feeds` is required school-owned: each link belongs to the school whose
timetable it serves. The public `.ics` endpoint has no school header; it finds
the link by its token hash, then runs inside the link's school and rechecks the
owner's access there ([calendar feed](../academics/calendar-feed.md)).
### HQ two-factor sign-in

`platform_staff_two_factor` is global: a platform staff member's own authenticator
app (encrypted secret, hashed recovery codes), read and written only by the
two-factor pages, the HQ staff page and the operator console. Platform staff
belong to no school ([two-factor sign-in](hq-two-factor.md)).
