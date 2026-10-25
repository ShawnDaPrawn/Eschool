# Early warning and owned interventions

The early_warning module defaults off. Rule-based observations are transparent
staff guidance, never a diagnosis, promotion decision or automated sanction.
Current-day jobs observe canonical academic/attendance evidence and retain
immutable school/year/grade/as_of history; missing or unavailable sources stay
unknown. Prior dates are not fabricated by replaying today's data. Academic
threshold, assessed share, attendance sample and absence thresholds are school
settings with explicit revisions. Behaviour counts integrate only authoritative
discipline records. Wellbeing remains unavailable until an explicit downstream
consent/disclosure projection exists; confidential/anonymous incidents and
counselling relationships are never inferred or joined into this feature.

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
