# Interests and strengths mirror (Action Plan 4.8)

A learner-only view of what is going well: where the learner is growing
fastest, what they remember well, what they chose to explore with the tutor,
the activities they take part in and the skills from them, what they read
(once the reading library exists), and a few gentle career ideas to look into.

It is a mirror for the learner, not a measure for anyone else. It never shows
a score, level, percentage, count, rank, class or school average, and it is
never used to stream, place, group, rank or report on a learner.

The module is `strengths_mirror` ("Interests and strengths mirror", product
module Learning). It is off by default, each school switches it on, and its
stages are junior and high school: an early years learner never signs in
(Action Plan 4.1) and never reaches it.

This step is the server, the API and the specification of the learner screen.
The learner web portal and app are Flutter and build the screen from this
contract.

## User stories

- As a learner, I open **My strengths** and see, in plain and warm words, the
  topics I am growing in, the topics I remember well, what I chose to explore
  with the tutor, my activities and the skills I am building, and some careers
  I might enjoy finding out about.
- As a learner, I choose to share a copy with one named guardian (who can
  approve my account) or one teacher who teaches me, pick which parts to
  share and for how long (up to 30 days), see when they last looked at it, and
  end the share whenever I want.
- As a guardian or teacher a learner shared with, I read the copy they shared
  with me, as it was on the day they shared it. I cannot change it, share it
  on, or see anything they did not choose.

## Privacy and the guard against streaming

- **Only the learner.** Every learner route requires a current,
  guardian-approved Student of the selected school whose only role there is
  Student, in a stage the module allows. Guardians, teachers, school leaders,
  school admins, platform (HQ) staff, impersonation and across-school context
  are refused (`StrengthsAccess`, the `DiaryAccess` rules).
- **Only for the person asking.** `StrengthsAccess` also refuses unless the
  account passed in is the signed-in user of the request. A job, command or
  another service cannot hand in a learner's account to read their mirror.
- **No other way in.** `StrengthsMirror::forLearner(User)` is the only way to
  work out a mirror and there is no method that takes a learner id. Shares
  are read only through `StrengthsShares`, which checks the reader is the
  learner or the named viewer. `StrengthsMirrorTest::test_no_other_code_reads_the_mirror_or_its_shares`
  fails the build if any code in `app/`, `routes/` or `database/seeders`
  outside this feature uses the mirror classes or the shares table, so the
  mirror cannot quietly become an input to streaming, placement, early
  warning, reports, the leadership dashboard or HQ. The sources it reads
  (mastery, practice, tutor sessions, activities, career profiles) keep their
  own features and rules; only this combined view is walled off.
- **Tutor content is never read.** The "explored" section reads only the
  `subject_id` and `topic_id` a tutor session was started on. Messages,
  answers, working, photos and anything said in a conversation are never
  queried (asserted on the payload in the tests).
- **Nothing comparative.** Each section lists names only, in the learner's own
  order. There is no number in the payload except ids (asserted on the
  payload in the tests). Nothing compares the learner with another child, a
  class or the school.
- **Not stored.** The live mirror is worked out on every read and never
  stored. Only a share stores a copy, encrypted (`encrypted:array`), hidden
  from the model's array form.
- Responses are private and uncacheable (`PrivateDomainResponse`).

## Sections

| Key | What it shows | Source and rule | Off when |
| --- | --- | --- | --- |
| `growing` | Up to 5 topics where the learner is growing fastest | Velocity, not level: the recent20-v1 mastery score is replayed at the start of the last `growth_window_days` (42) and now, from the evidence the learner and their family may see (released marks if the school opted in, otherwise practice, never staff-only marks), as the growth digest does. A topic counts when its level rose or its score rose by at least `min_score_rise_bp`. A first piece of evidence is not growth; unknown is never zero. Ordered by the size of the learner's own rise, then name. | `mastery` off |
| `remembering` | Up to 5 topics the learner remembers well | Daily practice memories (FSRS) with stability of at least `remember_min_stability_days` (7), a last rating of hard or better, and a recall today of at least `remember_min_recall` (0.85); school items by their topic, library items by the school's mastery topic for that library topic. Most stable first. | `daily_practice` off |
| `explored` | Up to 5 subjects, each with up to 5 topics, that the learner started tutor sessions on this school year | `tutor_sessions.subject_id` and `topic_id` only | `tutor` off |
| `activities` | Current memberships of active activity groups this school year (name, discipline, season, member or captain), and skills: "Leading others in …" for a captaincy, "Helping others learn …" for completed supervised peer tutoring this year (topic or activity skill) | `activity_group_memberships`, `peer_tutoring_sessions` (as tutor, completed) | both `groups` and `peer_tutoring` off |
| `reading` | Always empty for now, with "Books you read will show here once the reading library is open at your school." | A seam for the reading library (Action Plan 4.4), which is not in main yet. That step fills `items` and sets `available` from its own module. | always, until 4.4 |
| `might_enjoy` | Up to 3 career ideas, each with its dated public source | Published, unexpired career profiles of the school (Action Plan 4.9) that link one of the topics or subjects above. `because` names the learner's own topic or subject. Wording: ideas to explore, never a plan made for you; never a subject or stream recommendation. | `pathway` off |

A section whose source module is off has `available: false` and says "This is
not switched on at your school yet." It is never shown as empty evidence.

## Sharing

- The learner picks a recipient from `GET strengths/recipients`:
  - **guardian**: an active `guardian_links` row with `can_approve`, to a
    verified, active user who is a current Parent member of this school;
  - **teacher**: a current Teacher member of this school with a current
    teaching allocation to one of the learner's current teaching groups.
  Anyone else (a guardian who cannot approve, a teacher who does not teach
  the learner, another learner, a person at another school, an unknown id)
  is refused with 422 and nothing is written.
- `POST strengths/shares` takes `operation_id`, `recipient_user_id`,
  `sections` (one or more section keys) and optional `days` (1 to 30, default
  30). It stores an encrypted copy of the chosen sections of the mirror as it
  is now. A retry with the same `operation_id` returns the same share; using
  it for a different share is 409. At most 20 open shares per learner.
- `POST strengths/shares/{id}/revoke` ends it at once (idempotent).
- The viewer lists open shares made to them (`GET strengths/shared-with-me`)
  and reads one (`GET strengths/shared-with-me/{id}`). Revoked or expired
  shares, shares to someone else and shares from another school are 404.
  A share also stops showing when the learner is no longer a current,
  approved learner here, or the viewer no longer holds the approving
  guardian link or Teacher membership it was made for.
- Every share, revocation and view is written to the audit log
  (`strengths.share_created`, `strengths.share_revoked`,
  `strengths.share_viewed`) with ids, kind, sections and expiry only, never
  the copy's content. The learner sees `last_viewed_at` on their own list.

## API (v2)

`/api/v2/strengths`, guarded by `auth:sanctum`, school context, `role.any`,
`learner.approved`, `module:strengths_mirror` and `throttle:60,1`. All take no
query string; only the share route takes a body. See `openapi.yaml` (tag
"Strengths mirror").

| Method and path | Who | Returns |
| --- | --- | --- |
| `GET strengths` | learner | The live mirror |
| `GET strengths/recipients` | learner | Who they may share with |
| `GET strengths/shares` | learner | Their shares with status and last viewed |
| `POST strengths/shares` | learner | Creates a share |
| `POST strengths/shares/{share}/revoke` | learner | Ends a share |
| `GET strengths/shared-with-me` | guardian or teacher | Open shares made to them |
| `GET strengths/shared-with-me/{share}` | the named viewer | The read-only copy |

## Data

- `strengths_mirror_shares` (required school-owned): learner, owner user,
  viewer user and kind, operation id and request hash, chosen sections, the
  encrypted snapshot and its date, `expires_at`, `revoked_at`,
  `last_viewed_at`. Rollback is refused while rows exist.
- Settings in `config/strengths_mirror.php`.

## Learner screen (for the Flutter learner app and web portal)

**My strengths**, opened from the learner home and the learning area, shown
only when `/me/context` lists `strengths_mirror`.

- Header: the learner's first name, "This is yours. Only you can see it,
  unless you choose to share it." and the day it was worked out (`as_of`).
- One card per section in this order: growing, remembering, explored,
  activities, reading, might enjoy. Each card shows the section `title`, its
  `message`, and its items as chips or short rows (topic with its subject in
  a lighter style). No progress bars, stars, numbers or colours that rank.
  A card with `available: false` is shown muted with its message, or hidden
  if the learner prefers (a per-device choice).
- Activities: each activity with its season; a captain gets a small "Captain"
  label. Skills follow as plain sentences.
- Might enjoy: each idea with its summary, "Because you are growing in
  {because}", and a "Read the source" link that opens `source_url` outside
  the app.
- **Share** button: a sheet listing recipients (name, and relationship or
  subjects), checkboxes for the sections (all on by default, reading only
  when available), a duration (7, 14 or 30 days; 30 by default), and a clear
  line: "They will see a copy of today's view. You can stop sharing at any
  time." Confirm sends one `operation_id`, reused on retry.
- **My shares**: each share with recipient, sections, shared on, ends on,
  last viewed, and an **End sharing** action.
- Guardian and teacher apps: a **Shared with me** list in the child or class
  area, and a read-only page with the `notice` and the shared cards, the same
  card style, no share or export actions.
- Leaving the screen or the app going to the background discards the loaded
  mirror (as the diary does). The screen uses the existing Material 3 theme
  and components; text uses the server's wording.

## Acceptance tests (`tests/Feature/Learning/StrengthsMirrorTest.php`)

| Test | Checks |
| --- | --- |
| `test_the_learner_sees_their_own_growth_memory_choices_activities_and_ideas` | Happy path: velocity not level (a steady high topic and a first result are left out), stable memories only, explored topics, captaincy and peer tutoring skills, reading seam, a sourced career idea, no comparative keys or numbers, no School B data, no writes |
| `test_tutor_conversations_never_appear_only_the_topics_chosen` | Message and answer text never in the live or shared payload; only subject and topics; stored copy encrypted |
| `test_guardians_teachers_leaders_and_hq_cannot_read_the_mirror_without_a_share` | Guardian, non-approving guardian, teacher, other teacher, school leader and HQ refused; the service refuses an account that is not the signed-in user, and a call with nobody signed in |
| `test_a_share_to_an_approving_guardian_is_a_read_only_snapshot_and_audited` | Recipients list, idempotent share, 409 on a reused operation, chosen sections only, snapshot unchanged when live data moves on, last viewed, audit rows without content, viewer cannot revoke |
| `test_a_share_to_a_teacher_who_teaches_the_learner_works_and_others_are_refused` | Teacher share works; other teacher, non-approving guardian, another learner and unknown ids refused with nothing written; nobody else reads the copy |
| `test_a_revoked_or_expired_share_is_refused` | Revoked and expired shares are 404 and leave the list; at most 30 days; a guardian who loses the approving link loses the share |
| `test_another_learner_never_reaches_this_learners_mirror_or_shares` | Another learner sees only their own, cannot revoke or read the share |
| `test_school_b_never_reaches_school_a_and_a_teacher_at_another_school_cannot_be_named` | School B teacher refused as recipient; School B accounts refused in School A; School A share id not found in School B |
| `test_the_module_is_off_by_default_and_each_source_follows_its_own_switch` | Off by default, stages, a source module off marks only its section unavailable, module off is 404 everywhere and refused in the service |
| `test_an_early_years_learner_has_no_mirror` | Early years learner refused at the route and in the service |
| `test_requests_are_strict` | No query, unknown fields, empty, unknown or repeated sections refused |
| `test_no_other_code_reads_the_mirror_or_its_shares` | The guard: no code outside the feature uses the mirror classes or the shares table |

`tests/Feature/Contracts/OpenApiContractTest.php` covers every route
(unauthenticated and module off), and the feature test validates every
response against `openapi.yaml`.

## Not in this step

- The Flutter screens (learner, guardian and teacher) and the regenerated
  Dart client (`packages/eschool_api`).
- Reading: the section is a seam until the reading library (4.4) lands.
- Awards and house points: left out on purpose, because many awards are
  competitive ("top of the grade") and would bring comparison into the
  mirror. Persistence badges could be added later.
- A learner-facing "what would it take" career view; ideas link to the
  career profile's public source for now.
- Sharing with a coach or other staff who are not the learner's teachers,
  and sharing a PDF or link outside the app.
- Fluent review of the Afrikaans and isiZulu wording.

## Open questions for the owner

- Should a learner also be able to share with any teacher at the school (for
  example a coach or a counsellor), not only teachers who teach them?
- Should guardians without approval rights (for example a second parent) be
  allowed as recipients if the learner chooses?
- Is 30 days the right maximum, and should the learner see each view (now the
  last view only) rather than the last one?
