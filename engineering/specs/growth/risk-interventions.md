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
requests in the forthcoming HTTP layer. No private input enters global plaintext
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

This domain increment precedes its typed HTTP contracts and panel/app workflows;
those consumers are not represented by placeholder navigation.


Current-state ordering deduplicates only against the immediately latest same-day
observation. A real A → B → A correction appends another immutable row, so readers
that select the latest ID show the restored facts. A forward migration retains
old rows and changes the historical fingerprint uniqueness constraint to a lookup
index; rollback refuses histories containing genuine repeated fingerprints.
The hourly refresh is registered in the active routes/console.php schedule.
