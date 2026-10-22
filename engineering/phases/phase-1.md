# Phase 1: many schools on one platform, and who people are

**Goal.** Many schools share one install, and no school can see another's data. Phase 1 also settles who people are, which every later phase relies on: memberships in schools, enrolments with dates, one student number for each learner for good, guardians who must approve a learner's account, profile fields each school can set up, switching modules on and off, notification settings, newer ways to sign in, each school's own crest and name, and a platform admin area for setting up new schools. The v1 API keeps answering, so apps already installed keep working. (The full specification is in the Build Bible, section 7, which is private; this plan says what we build in our own words.)

Open questions and the defaults taken are in [phase-1-questions.md](phase-1-questions.md).

## Work streams

| Stream | Builds | Depends on |
| --- | --- | --- |
| T1 Tenancy | `schools`, `school_id` on every school-owned table (back-filled to the existing school), `BelongsToSchool`, `CurrentSchool`, `school_memberships`, the `school.context` middleware, Spatie teams mode, per-school settings and push topics, a school switcher for people at several schools, the `/me/context` contributor registry, a second demo school and the cross-school test suite | Phase 0 |
| T2 Identity | Dated enrolments, the lifelong student number and handle, guardian links and approval before a learner can sign in, invitations, email verification, passkeys, Google and Microsoft sign-in limited to the school's domains, one-time email sign-in links, avatars and photos (server side) | T1 |
| T3 School configuration | Profile fields by category, module switches (`config/modules.php`, `school_modules`, `module:` middleware, `@module`), notification types, rules and preferences with `Notifier`, the complete `/me/context`, school branding and the My school page | T1 |
| T4 Platform and data | The platform admin area (`/saas`): school wizard, packages and module switches, usage, audited impersonation; the import toolkit (CSV and SA-SAMS exports with mapping, validation, dry run, rollback); consent records, retention rules and personal-data export | T1 |
| T5 Apps | Child switcher and school switcher, deep links, the approval, avatar, My school and Notifications screens in the shared packages, the avatar set | T1, with the contract below |
| T6 Close | The role matrix over every route, self-test record, security record, docs, build log | all |

## Contract between streams

Fixed up front so streams can work in parallel:

- **Tables.** T1: `schools`, `school_memberships` (user, school, role, status, starts_on, ends_on), `school_id` everywhere it belongs. T2: `enrolments`, `students.student_number`, `users.handle`, `handle_changes`, `guardian_links`, `learner_approvals`, `guardian_invitations`, `sign_in_methods`, `webauthn_credentials`, `email_sign_in_links`. T3: `profile_field_categories`, `profile_field_definitions`, `profile_field_values`, `school_modules`, `notification_rules`, `notification_preferences`, branding as `settings` rows with `school_id`. T4: `packages`, `subscriptions`, `usage_meters`, `support_access_log`, `imports`, `import_rows`, `consents`, `retention_rules`, `data_export_requests`.
- **`/me/context`.** T1 turns it into a composition of contributor classes listed in `config/context.php`; each stream adds its own contributor and never edits another's. Keys: T1 `user`, `schools`, `current_school`; T2 `sign_in_methods`, `children` (adding `student_number`, `approved` and `homeroom`), `approval`; T3 `modules`, `notification_rules`, `profile_fields`, `feature_flags`, and the school's branding inside `current_school`; T4 `impersonation` (while active).
- **Middleware aliases.** T1 `school.context`; T2 `learner.approved`; T3 `module:<key>`. **Blade:** T3 `@module('key')`.
- **v2 endpoints.** T2 `/api/v2/guardian/approvals`, `/api/v2/guardian/invitations/{token}`, `/api/v2/auth/passkeys/*`, `/api/v2/auth/oauth/{provider}`, `/api/v2/auth/email-link`, `/api/v2/me/avatar`, `/api/v2/me/handle`. T3 `/api/v2/me/notification-preferences`, `/api/v2/schools/{school}/my-school`, `/api/v2/me/profile`. Each lands in `openapi.yaml` with its stream.

- **Response shapes the apps rely on.** T5 builds against these; T2 and T3 implement them exactly and add them to `openapi.yaml`. Every v2 response uses the v2 envelope, ids are integers, dates ISO 8601 and URLs absolute.
  - `/me/context` grows only by adding: every key and field in Phase 0's contract (`Context`, `ContextUser`, `SchoolMembership`, `SchoolSummary`, `CurrentSchool`, `Branding`, `SessionYear`, `Module`, `NotificationRule`, `Child` in `openapi.yaml`) keeps its name, type and nesting, and old clients keep decoding it. So `schools` stays a list of `{school: SchoolSummary, roles}`; `current_school` keeps `tagline`, `branding`, `timezone` and `session_year`; `modules` stays a list of `{key, read_only}`; a notification rule's `mode` stays `mandatory`, `default_on`, `default_off` or `off`; a child keeps `first_name`, `last_name`, `name`, `image` (the avatar or photo URL) and a nested `school`. The roles at the current school are `user.roles` and `schools[].roles`; there is no separate `roles` key.
  - Phase 1 adds: on `Child`, `student_number` (string or null), `approved` (boolean) and `homeroom` (string or null); a new key `approval` (`{status: pending|approved|withdrawn, approved_by_count}` for a learner, null for everyone else, from T2). The school's crest, names and colours that T3 manages reach the apps through `current_school` and `SchoolSummary`, read from the school's settings rows; there is no separate `branding` key. New fields are optional for clients: an app treats a missing field as "not available yet".
  - Choosing a school: the apps send `X-School-Id` on every call once a school is chosen; a guardian's child screens send the child's `school.id`.
  - In the endpoints below, a learner is `{id, name, image, student_number, school: SchoolSummary}`.
  - `GET /api/v2/guardian/approvals` = list of `{learner, status, requested_at, consent: {version, text}}`; `POST /api/v2/guardian/approvals/{learner}` with `{decision: approve|withdraw, consent_version}`.
  - `GET /api/v2/guardian/invitations/{token}` = `{school: SchoolSummary, email, learners: [{id, name}], expires_at, needs_password}`; `POST /api/v2/guardian/invitations/{token}/accept` with `{password?}` answers like a sign-in.
  - `GET /api/v2/me/notification-preferences` = list of `{type, label, module, channels: {push|email|in_app: {enabled, locked, reason}}}`; `PUT` with `{type, channel, enabled}` (refused with 422 for a locked one).
  - `GET /api/v2/schools/{school}/my-school` = `{school: SchoolSummary, website, phone, email, address, documents: [{title, kind, url}], term_dates: [{name, starts_on, ends_on}]}`.
  - `PUT /api/v2/me/avatar` with `{avatar_key}` (a key from the app's avatar set) or a multipart `photo`; answers `{kind: set|photo, key, url, status: approved|pending}`.
  - Push data: `link` holds an app path (`/notifications`, `/my-school`, `/approvals`, `/invite/<token>`, or a feature's own path) and `school_id` the school it belongs to; the apps switch school before opening it.

## Exit gate

Our checklist (the Build Bible, section 7, has the gate itself): no API route hands one school's records to another school (it answers 403 or 404); no learner signs in before a guardian approves; a new school goes from the platform admin, through a spreadsheet import, to parents approving their children, end to end; apps installed before Phase 1 keep working; the phase's UX and security checks pass; and the product owner sees a recorded demo. Status is kept in the [build log](../bible/build-log.md); the self-test and security records (`engineering/self-tests/phase-1.md`, `engineering/security/phase-1.md`) are written at the close.
