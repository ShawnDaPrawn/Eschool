# Wellbeing check-ins and the intervention library

Action Plan 4.11. Two pastoral tools that grow the existing early warning
workbench ([early warning and owned interventions](risk-interventions.md)) and
the wellbeing module ([incident reporting and restricted casework](../community/wellbeing.md)).
They are age-appropriate, private, and feed early warning as context only.

## User stories

- As a principal, I keep a library of everyday school actions (a name, the
  concern area, the steps, a suggested length and how to measure progress), so
  staff start a follow-up from something the school agrees works.
- As a principal, I adopt NavigateEd's suggested actions in one step and then
  change them to suit the school.
- As a teacher, grade head or pastoral professional who can already plan an
  intervention for a learner, I start it from a library action, and I record
  dated progress against the plan's steps: on track, needs review or completed.
- As a learner's counsellor or safeguarding professional, I schedule a short
  check-in for one learner or a group on my caseload, and I record the answer
  the learner gives me: a five-point scale and an optional note.
- As that professional, I see on my early warning list how recent check-ins
  went for my own learners, as context, without it changing anyone's risk.

## Rules

### Intervention library

- The library is school-owned (`intervention_library_actions`). Nothing is
  added to a school until a principal adopts the suggested actions or writes
  its own. The six suggestions are everyday, non-clinical school actions
  (a named adult check-in, an attendance plan, catch-up support, a homework
  routine, a peer buddy, a positive behaviour goals card).
- Concern areas: attendance, learning, engagement, behaviour, friendships and
  belonging, wellbeing. An action has one to eight steps, a suggested length of
  1 to 26 weeks and a measure of progress.
- Anyone who can open the intervention workbench reads the active actions.
  Only a dated principal (`RiskAuthority::principal`) adds, changes, archives or
  restores one, or adopts the suggestions. Changes carry a revision; a stale
  page is refused (409). An archived action is no longer offered; adopting the
  suggestions again never brings an archived one back.
- Creating an intervention keeps the existing rules (owner, authority kind,
  dates, current year). Choosing a library action copies its plan (name, area,
  steps, length, measure) into the intervention's encrypted `plan` column. If
  the author leaves the action text blank, it is written from the steps. Later
  library edits never change a plan already made.
- The plan and its progress are body records: only the intervention's exact
  current owner with fresh authority reads or adds them, exactly as for the
  action text. Grade heads and principals keep seeing queue metadata only.
- A progress entry has a date (not before the start, not in the future), an
  optional step from the plan (or the whole plan), a status (on track, needs
  review, completed) and an optional encrypted note. Entries are append-only.
  A completed or cancelled intervention takes no more progress. A step marked
  completed does not complete the intervention; its owner still reviews it.
- The v2 API is unchanged: the panel alone offers library actions, plans and
  progress.

### Wellbeing check-ins

- A check-in is a wellbeing record (`wellbeing_check_ins`), behind the
  wellbeing module. It needs early warning too, because the learner-level
  grant that names a learner's pastoral professional is the dated risk caseload
  grant.
- Authorised pastoral staff for a learner are exactly the professionals who
  pass `WellbeingAccess::professional` (a fresh Counsellor or Safeguarding role
  with `pastoral-sensitive-access`, the wellbeing module, no platform identity,
  impersonation or pending password change; see
  [restricted pastoral access](../platform/restricted-pastoral-access.md)) and
  hold a current dated risk caseload grant for that learner in the current year.
  Teachers, grade heads, principals and administrators never schedule, read or
  record a check-in, even with a risk caseload grant and no pastoral role.
- A professional schedules one learner, or a group of up to 40 chosen from
  their own caseload, for a day from today to 60 days ahead. A group is all or
  nothing: every learner must be on the caseload and currently enrolled. The
  same question for the same learner on the same day is refused.
- The question is one of four short, plain prompts ("How has your week been?",
  "How are you feeling today?", "How is school going for you at the moment?",
  "How are things with your friends?"). The answer is Not good, A bit low,
  Okay, Good or Great, and an optional note of up to 500 characters. There are no
  clinical questions.
- In this step the professional records the answer the learner gave in person
  or on paper (`answer_source` = staff). The server side of the learner's own
  answer exists (`CheckIns::dueForLearner` and `answerAsLearner`): a learner
  sees only their own due check-ins and answers each once; nobody else's.
- The answer is encrypted. The audit log records ids, revisions and the answer
  source, never the answer. A refused write never flashes the note into the
  session. Every change is bound to the account and school that showed the
  page and carries nothing in the query string.
- A professional whose grant ends, or who loses the pastoral role, loses the
  answers too, including the ones they recorded.

### Early warning context

- On the early warning list, `CheckInContext` adds, for the reader's own
  authorised learners only, how many check-ins were answered in the last six
  weeks and the latest answer, labelled as context that does not affect the risk
  score. It is read on request and never stored.
- Check-ins never write risk observations, never change a score or the list
  order, never raise a risk, are never shown to other learners and are never
  used to rank anyone. Grade heads, teachers and principals see no check-in
  context.

## Data

| Table | Columns |
| --- | --- |
| `intervention_library_actions` | school, name, concern area, steps (JSON list), suggested weeks, progress measure, default key (unique per school), created by, archived at, revision |
| `interventions` (added) | `library_action_id` (nullable), `plan` (encrypted copy of the library action) |
| `intervention_progress_entries` | school, intervention, actor, recorded on, step (nullable), status, note (encrypted), created at; append-only |
| `wellbeing_check_ins` | school, learner, school year, scheduled by, group id (shared by a group), prompt, due on, status (scheduled, answered, cancelled), answer (encrypted scale and note), answer source (staff or learner), answered by, answered at, revision |

All four are required school-owned tables. The migrations refuse to roll back
while rows exist.

## Panel

- `/risk-interventions/library`: the library; principals also adopt, add,
  change, archive and restore actions. Linked from the workbench tabs.
- `/risk-interventions`: the create form offers the library; an owner's open
  intervention shows its plan, its progress and a progress form
  (`POST /risk-interventions/interventions/{id}/progress`, with the workbench's
  operation receipts). The early warning list shows check-in context.
- `/wellbeing-check-ins`: schedule, record and cancel check-ins. In the
  Wellbeing menu for authorised pastoral professionals only.

## Acceptance tests

| Test | What it proves |
| --- | --- |
| `InterventionLibraryTest::test_a_principal_adopts_the_suggested_actions_once_and_changes_them` | Suggestions are adopted once, stay archived when archived, changes need the current revision, steps are bounded, the panel splits steps by line |
| `InterventionLibraryTest::test_a_teacher_starts_an_intervention_from_the_library_and_records_progress_against_its_steps` | Plan copied and encrypted, blank action written from steps, v2 detail unchanged, library edits never rewrite a plan, progress dated and bounded, notes encrypted and kept out of the audit log, entries append-only |
| `InterventionLibraryTest::test_the_panel_offers_the_library_and_records_progress_without_flashing_the_note` | Panel create from the library, plan and progress shown escaped, replayed operation adds nothing, refusals never flash |
| `InterventionLibraryTest::test_only_a_principal_changes_the_library` | Wrong role: teacher and grade head read but cannot change (403, nothing written); guardian refused; account binding and query-string refusal |
| `InterventionLibraryTest::test_someone_who_does_not_own_the_intervention_cannot_read_or_add_progress` | Wrong owner: grade head and principal get 404 for plan and progress; an owner who loses authority loses the plan |
| `InterventionLibraryTest::test_another_schools_library_actions_and_interventions_are_refused` | Wrong school: School B's action and intervention ids give 404; School B's rows unchanged; no School B text shown |
| `InterventionLibraryTest::test_archived_actions_are_not_offered_and_completed_plans_take_no_more_progress` | Archived actions refused for new plans, existing plans keep their copy, completed interventions take no progress |
| `CheckInsTest::test_a_pastoral_professional_schedules_records_and_reads_a_private_check_in` | Happy path through the panel; answer encrypted, not in the audit log, escaped; menu item shown |
| `CheckInsTest::test_a_group_check_in_needs_every_learner_on_the_professionals_caseload` | Group all-or-nothing, shared group id, duplicates and out-of-range days refused |
| `CheckInsTest::test_teachers_grade_heads_and_the_principal_never_see_or_change_check_ins` | Wrong role: a non-pastoral teacher (even with a caseload grant), a grade head and the principal get 403 everywhere, no menu item, no context; nothing changes |
| `CheckInsTest::test_a_counsellor_without_the_learners_caseload_cannot_read_or_change_their_check_ins` | Wrong owner: another counsellor gets 404 and sees nothing; a revoked grant removes access |
| `CheckInsTest::test_another_schools_learners_and_check_ins_are_refused` | Wrong school: School B's learner and check-in ids give 404; School B's row unchanged |
| `CheckInsTest::test_check_ins_feed_early_warning_as_context_only_and_never_raise_a_risk` | Recompute adds and scores nothing; context only for the reader's own learners; note never shown on the list |
| `CheckInsTest::test_the_learner_answers_only_their_own_due_check_in` | Server-side learner path: own due check-ins only, once; other learners, guardians and staff refused |
| `CheckInsTest::test_writes_are_bound_to_the_account_conflicts_are_refused_and_the_note_is_never_flashed` | Account binding, query-string refusal, stale revision and cancelled check-ins refused, no old-input flash |

## Not in this step

- The learner app screen and the v2 API for a learner to see and answer their
  own check-ins (the server side exists and is tested); a notice to the learner
  when a check-in is due. OpenAPI and the generated Dart client are unchanged.
- Check-ins in the staff apps; library and progress in the v2 risk API.
- Stage-aware wording (Action Plan 4.1) beyond the plain prompts: pictures or
  spoken prompts for Foundation Phase.
- Translating the suggested library actions: a school adopts them in English and
  edits them; translated suggestions are a later step.
- Using the consultations engine: its managers can read named answers, which
  check-ins must never allow, so check-ins use the wellbeing professional
  boundary instead.
