# Early warning and owned interventions

The early_warning module defaults off. Rule-based observations are transparent
staff guidance, never a diagnosis, promotion decision or automated sanction.
Current-day jobs observe canonical academic/attendance evidence and retain
immutable school/year/grade/as_of history; missing or unavailable sources stay
unknown. Prior dates are not fabricated by replaying today's data. Academic
threshold, assessed share, attendance sample and absence thresholds are school
settings with explicit revisions. Behaviour counts integrate only authoritative
discipline records. Shared wellbeing observations remain unavailable; confidential or anonymous incidents are never inferred or joined. A separate on-read private context can show only a fresh Counsellor’s own explicitly authorised active cases when the same reader also has the current RiskCaseload grant. This count is not stored, scored or included in leadership/classroom aggregates. See [own-case context](counsellor-case-context.md).

Dated grade heads use existing AcademicLeadership. Restricted professionals need
both SensitiveRecordsAccess and a separate explicit dated risk caseload. Note,
support-plan or wellbeing case grants do not confer risk access. Teachers may
read academic/attendance guidance only for allocated learners. Families receive
no risk labels. Principals administer assignment metadata. Dated principals and school admins
with academic-management permission receive only
history-based grade/date counts, not learner identities, intervention text or
clinical labels. Platform identities and impersonation gain no private access.

Interventions have an explicit fresh authorized owner, action, start/review dates,
status and outcome. Text and immutable revisions are encrypted LONGTEXT. Body
reads require current owner authority before SQL retrieval; metadata queues show
due/status/owner only. Ownership never substitutes for current role/caseload.
New cases use the current school year; owned active actions may continue across
rollover while preserving that reporting year. ACL revocation also works for
retained years. Mutations use optimistic revisions and private body-only
requests in the private HTTP layer. No private input enters global plaintext
replay or session flash. Principal metrics consume dated observation history,
not today's enrolment joined backwards. Shared dashboard adapter counts distinct
learners from its supplied dated cohort, returns null for unavailable sources,
and independently rechecks this aggregate policy.

Grade evaluation holds a connection-owned school/year/grade mutex before reading
evidence, verifies it across source processing and releases it after completion.
Each immutable insertion uses a short school-row transaction and rechecks the
current dated grade, so moved or ended enrolments cannot append stale grade facts.
Hourly and ID-only outbox jobs observe today; retained-year replay does not invent
historical data. Explicit owner handoff requires a fresh eligible replacement of
the original authority kind and never retrieves the action or outcome. Metadata
can remain visible to grade leadership after a professional grant ends, but body
availability is independently rechecked against the original professional grant.

The domain, typed HTTP contract and actual staff panel/app workflows are
implemented in separate reviewable increments.

## Private v2 contract and client

The additive `growth/risk` surface supplies configuration, paged learner and
professional choices, eligible owners, scoped signals/class guidance, metadata
review queue, original-owner body/history, creation/review/handoff, separate
caseload assignment/revocation and versioned rules. Every operation includes
optional expected-account binding and school selection. Private writes reject
all query parameters before validation; immutable UUID intent is HMAC keyed and
receipts use a separate encrypted LONGTEXT `risk_operations` ledger. Replays
reauthorize current scope and the original professional owner kind; another
staff role cannot revive a withdrawn professional source.

Ordinary allocated staff or assigned professionals create actions for themselves.
Assigning another owner requires a fresh principal or dated grade-head metadata
administrator before the recipient is looked up. Eligible-owner choices are
bounded; ordinary staff cannot enumerate other professional caseload holders.
Handoffs remain pinned to the original authority kind. The optional requested
owner kind is freshly validated, allowing an explicitly academic action for a
dual-role teacher without silently acquiring professional access. Detail/history
still require the exact current owner. No principal assignment picker loads body
columns. Retained active actions can be reviewed without rewriting their year.

The owned OpenAPI fragment and canonical contract preserve all preceding paths
and schemas; generated Dart models use calendar strings for day-only fields and
nullable scores for unavailable sources. Actual staff panel/native consumers
share the private repository; no learner/parent risk navigation is introduced.



## Staff workbench and applications

The actual workbench separates review actions, class follow-up, scoped signals,
metadata-only caseload administration and principal rule settings. Principal
assignment metadata includes retained action IDs and names for eligible handoff,
without loading action/outcome; only the exact original-kind owner can open the
body. Private drafts remain in memory or the direct escaped response. Historical
refusals authorize the same school/account/record before reflecting a draft,
never session-flash it. Conflicts require explicit comparison with the fresh
revision. Account/school changes invalidate in-flight app replies and clear
private screens. Family shells receive no risk route or label.
Current-state ordering deduplicates only against the immediately latest same-day
observation. A real A → B → A correction appends another immutable row, so readers
that select the latest ID show the restored facts. A forward migration retains
old rows and changes the historical fingerprint uniqueness constraint to a lookup
index; rollback refuses histories containing genuine repeated fingerprints.
The hourly refresh is registered in the active routes/console.php schedule.

## Renewals into a new year

Open risk caseload grants are offered for renewal into the next school year by caseload administrators only: see [caseload renewals](caseload-renewals.md) (Action Plan 1.20).

## No child unseen (Action Plan 4.5)

Early warning finds learners who are struggling or absent. This rule finds the
quiet ones: learners whom no adult has recorded a positive contact with
recently. It is a prompt for staff to reach out, not a label for the learner.

### User stories

- As a grade head, I see which learners in my grades have had no recorded
  positive contact from any adult in the last N school days, how long it has
  been, and which kinds of contact they have had this year, so I can make sure
  someone reaches out.
- As a principal, I see the same list for the whole school, or one grade.
- As a grade head or principal, I name a staff member at my school to reach out
  to an unseen learner. They get a notification and can see the learners they
  were asked to check in with.
- As a principal, I set N, the number of school days without contact.
- As anyone, I can rely on the flag clearing once a positive contact is recorded.

### Rules

- **Sources.** Only these count, and only their dates and kinds are read, never
  their text, reasons, titles or criteria:
  - progress notes (`learner_notes`) with tutor or staff visibility that are not
    archived; counselling notes never count;
  - award nominations that are pending or approved, and active award
    presentations (`awards` module);
  - house points above zero that are not a reversal and have not been reversed
    (`house_points` module); deductions never count;
  - completed peer tutoring sessions, which are supervised by an adult, for both
    the tutor and the tutee (`peer_tutoring` module).
- **Never used.** Counselling, wellbeing, health, safeguarding, support-plan and
  discipline records. School consultations in main are polls, surveys and
  elections, not one-to-one conversations, so they are not a source either.
- **School days.** The school's register teaching days (Mon–Fri unless the
  school changed them), without the year's holidays and school-wide term breaks,
  from the start of the school year to today.
- **N** is a school setting (`unseen_school_days`), default 15, between 5 and
  60, set by a dated principal. Saving it queues a fresh early-warning run.
- **Unknown, never zero.** A source counts only when its module is on and the
  school recorded at least one contact of that kind in the window. If no source
  is in use, or the year has fewer than N school days so far, every learner is
  "unknown" and nobody is flagged. A switched-off module's tables are not read.
- **Unseen** means a source is in use and the learner's latest contact from the
  sources in use is before the first of the last N school days. Otherwise the
  learner is "seen".
- **The early-warning rule.** `RiskRecompute` records an `unseen` observation
  for each learner alongside the other sources: score 100 when unseen, 0 when
  seen, null when unknown; signal count = school days since the last contact
  (null when unknown or no contact this year); reason `no_positive_contact`,
  `positive_contact_recorded`, `source_unavailable` or `insufficient_evidence`.
  Evidence holds N, the window start, the sources in use, the kinds this year
  and the last contact date only. The `unseen` source is not part of the risk
  signals list or its API contract, and does not add to observed scores.
- **Who sees it.** Current dated grade heads (current year) for their grades; a
  dated principal for the whole school, with learner names, because the plan
  asks for a whole-school list. This is wider than the principal's counts-only
  view of risk signals; it carries no risk score, no clinical label and no note
  text. School admins without a principal appointment, ordinary teachers,
  families and platform identities get nothing. A named adult sees only their
  own open learners.
- **Named adult.** A current grade head of the learner's current grade, or a
  dated principal, names one active staff member of the same school for a
  learner who is unseen today. Naming another adult closes the earlier request
  (`reassigned`). Each naming is audited (`growth.unseen_adult_assigned`, ids
  only) and sends `growth.unseen_adult_assigned` to that adult only; the notice
  names no learner.
- **Clearing.** The lists are worked out on every read, so a learner leaves them
  as soon as a positive contact is recorded. The next early-warning run (hourly
  `risk:evaluate`, or an outbox-triggered run) closes the open request
  (`contact_recorded`) and audits it (`growth.unseen_adult_cleared`).
- Writes go through `UnseenLearnersRequest` and `UnseenLearners`, which check
  the module, the actor, the grade and the school again. Writes carry the
  expected account and school and nothing in the query string.

### Data

`unseen_assignments` (school-owned): learner, school year, named adult, who
named them, the day, and when and why it was closed. One open row per learner
and year. Rollback refuses to drop the table while it holds rows. N is the
`unseen_school_days` settings row. Observations use the existing
`risk_observations` table with source `unseen`.

### Pages

`/risk-interventions/unseen` (early_warning module): a grade selector (all my
grades, or the whole school for a principal), the sources counted and the
window, then one row per unseen learner: name, grade, school days since the last
positive contact (or "No positive contact recorded this year"), kinds of contact
this year, the named adult, and a form to name one. The principal also sets N
there. The Early warning workbench links to it for grade heads and principals.

### Acceptance tests (`tests/Feature/Growth/NoChildUnseenTest.php`)

| Test | Checks |
| --- | --- |
| `test_unseen_learner_is_listed_for_the_grade_head_and_a_named_adult_is_assigned_audited_and_notified` | Happy path: the unseen learner is listed for the grade head without note text; naming an adult over HTTP saves it, audits it and shows it; the named adult sees their learner. |
| `test_a_learner_with_recent_positive_contact_is_not_flagged_and_a_contact_clears_the_flag_and_named_adult` | Recent contact is not flagged; school-day counts; a new contact clears the list at once; naming for a seen learner is refused; the next run closes the request. |
| `test_switched_off_or_unused_sources_give_unknown_never_unseen` | No data, module off, a source used only before the window and too few school days all give "unknown", never unseen. |
| `test_clinical_negative_reversed_voided_and_cancelled_records_never_count` | Counselling and archived notes, deductions, reversed points, voided awards and cancelled sessions never count; a completed session counts for tutor and tutee. |
| `test_a_teacher_without_grade_head_rights_is_refused_the_list_and_the_assignment` | Ordinary teacher, other-grade head, parent: 403 and nothing written; only the principal sets N; query-string writes are refused. |
| `test_school_b_learners_never_appear_for_school_a_and_cannot_be_assigned` | School B's placed learners never appear for School A; naming for a School B learner is 404 and School B's rows are unchanged. |
| `test_an_adult_from_another_school_is_refused` | Another school's staff member, a learner or a parent cannot be named; nothing is written or sent. |
| `test_the_notification_goes_only_to_the_named_adult_and_names_no_learner` | The notice goes to the named adult only and names no learner; naming another adult closes the first request and notifies only the new one. |
| `test_the_hourly_risk_evaluate_command_records_the_same_unseen_rule` | `risk:evaluate` is scheduled and queues School A only; the job records the same result as the rule, with no note text; the signals list keeps its four sources; unknown when nothing is used; nothing when early warning is off. |

### Not in this step

- The My grade hub in the apps (`/api/v2/my-grade`) and the v2 risk contract:
  showing the list there needs an API change and a regenerated Dart client, so
  this step is panel and server only.
- A count on the leadership dashboard; the principal's whole-school list is on
  the No child unseen page.
- Gentle repeat reminders to the named adult until a contact is logged.
- Who the default named adult should be (homeroom teacher, grade head or a
  chosen mentor) is still an open owner question; for now the grade head
  chooses any staff member.
- One-to-one conversations as a source: there is no such record in main yet.
