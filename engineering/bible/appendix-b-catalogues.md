# Appendix B: module and notification catalogues

Phase 5 fee-plan configuration and annual-rate preview use the existing `fees`
module and the explicit school permission `fee-plans-manage` (Super Admin default;
grant explicitly to custom Finance roles). No new payment method, wallet/vendor
entitlement or notification type is enabled by this increment.

Family configuration uses `fee-accounts-manage`; annual bill preview, approval,
posting and finance invoice history use `fee-billing-manage`, independently of
plan editing. Super Admin defaults include both, and demo Finance has explicit
grants. Custom Finance roles need explicit grants. All use the existing `fees`
module, including retained read-only history. No new notification/delivery or
payment provider is enabled.

The modules and notification types NavigateEd's registries hold, phase by phase. The registries in code will be `admin-panel/config/modules.php` and `admin-panel/config/notification_types.php`, introduced in Phase 1. This page lists only what exists in the code; the planned catalogues are kept in the private Build Bible (Appendix B) and entries are added here when a phase builds them.

## Phase 1 module registry

Implemented entries: `school`, `people`, `academics`, `communication` (required),
`chat`, `online_exams`, `fees`, `cards`, `intake`, `web_learner`, `web_parent`
and `web_teacher`. Chat, online exams and portals default on. Explicitly
packaged new schools start fees, cards and intake off; upgraded schools keep
their seed features. Fees with retained records become read-only when disabled.
Package entitlement is separate from a school's switch. The notification registry and delivery path are described below.

## Phase 0

Phase 0 added no module and no notification type: `config/modules.php` and `config/notification_types.php` arrive in Phase 1. The web builds of both apps shipped without a switch; `web_<portal>` (one switch per portal, default on) is the first entry of Phase 1's module registry. The queued mail and push jobs of Phase 0 (`SendMail`, `SendPush`) carry the existing notifications unchanged and record each attempt in `notification_deliveries`; the notification types and their rules attach to them in Phase 1.

## Phase 1 notification registry

Account verification, password reset, invitation and sign-in links require
email. New-device notices, learner and handle approval requests, consent
requests and urgent school notices require their offered channels. Parents'
absence alerts are required. Optional school notices and direct messages
default push/in-app on and email off; assignment, submission, content, marks
and reports use the same defaults on their offered portals. Fees invoices,
reminders and receipts default email on as well. Future safeguarding alerts
remain unavailable until their module and permission are implemented.

The registry is `config/notification_types.php`. Schools control optional
portal/channel modes, humans control their optional choices, and `Notifier`
enforces module availability, current access and precedence before delivery.
The [specification](../specs/platform/notification-rules.md) records semantics.

## Phase 2: curriculum permissions

Always-on academics includes curriculum-read/manage/publish. School publishers
manage all contexts; teacher read/manage also requires an explicit subject,
grade-mapped homeroom and year allocation. Fresh access checks precede cached
write replay. Plan saves, copies and publication create audit records. No new
family notification is emitted for draft definitions.

## Phase 2: structure authority and events

academic-structure-manage permits school-scoped group/membership/allocation and
leadership administration. Teaching capture requires a current canonical
allocation; future assignments permit curriculum planning. Dated grade heads and
principals have explicit grade/year authority for subsequent academic flows.
academics.assignment_changed is a transactional outbox event carrying record IDs
and kind only, ready for academic consumers. It sends no family notification on
its own. The per-minute projection refresh is a convenience; canonical date
checks remain authoritative when it is delayed.


## Phase 2: marks permissions and events

marks-view-all and marks-release are explicit school permissions, separate from
structure administration. Current allocated teachers capture; dated grade heads
moderate/correct their grade with reasons; scoped grade heads/principals or school
releasers release selected results. academics.marks_changed and
academics.assessment_published queue school-scoped recomputation; assignment
changes recompute affected histories. academics.marks_released queues generic
family notices through Notifier with current guardian relationships and the
existing academics.marks_released preference rules. No score/person details enter
notification event payloads. The family API resolves current own-child rights.


`academics.timetable_changed` records school-scoped lesson IDs transactionally
when a placement batch commits. It contains no learner list. The normal scheduler
refreshes compatible v1 current-week projections; the canonical timetable remains
authoritative while a projection refresh is delayed.


Academic register amendment: academics.register_amended (academics module),
current guardians, standard channel preferences; attendance.absence retains its
mandatory-parent rule. academics.register_changed carries register/entry IDs and
revisions through the outbox; no personal body is stored in that domain event.

Reports: academics.report_approved carries batch ID/content fingerprint to queued
per-card generation; academics.report_published carries batch ID to Notifier.
Notification type academics.report_published uses the academics module and
existing family channel preferences. Claims are per card/recipient; prior read
state does not consume notification eligibility. Mail/push transport rechecks
released evidence and fresh guardian/learner rights. No report scores, comments,
signed download links or PDF bytes enter domain event payloads.


## Mid-year academic changes (24 October 2026)

School-owned `academic_transition_policies` version grade defaults;
`academic_transitions` stores immutable dated facts with encrypted reason/input/result;
`academic_exit_packages` stores requester-owned queued generation attempts,
encrypted source/manifest and private artifact receipts; `retention_rules` stores
explicit copy expiry and source-record retention. Enrolments gain additive soft
cancellation/history state. No new public notification type is required: generic
context-change events refresh projections, never send private transition reasons.


## Phase 2: content publication

`academics.content_published` uses the academics module and the standard school
notification rules/preferences. Recipients are the current learner and verified
current guardians of each currently enrolled published-group member. The generic
notice contains no title/source/transcript; the delivery payload contains only
the version/learner IDs and app path. Delivery rechecks access, destination and
preferences; obsolete publications, ended guardians and withdrawn material are
suppressed. Each version/learner/user has one notification claim and separate
read state. Content upload/publication use ID-only transactional outbox events.

Publication fanout is ID-only queued work in batches of at most 100 learners.
A durable cursor on the current published item resumes after each batch; new
publication resets it, and obsolete/withdrawn publications stop before sending.


### Communication centre message types

`communication.message` is an optional communication-module notice for all
profiles. `communication.approval_requested` is offered to staff reviewers.
School rules and personal choices apply; the author selects a subset of channels
for publication. The existing `comms.message` remains the chat-module type.
Reader notices use `/communication/messages?message=<id>`. Reviewer notices use
the named private workbench `/communication-centre?message_id=<id>`; their
emails use the trusted panel host even when the family portal has another origin.
Both types contain generic text and IDs only. Delivery checks publication identity or current pending revision plus
recipient/reviewer rights immediately before transport. Withdrawals and ended
guardian rights suppress delivery; notification contents never contain the
private title, text, custom contacts or read statistics.


## Activity groups

`groups`: school-entitled and disabled by default; activity hierarchy, dated staff/
learner allocations, sessions, registers and authored materials. Enabling the
module exposes its actual backend operations, not unimplemented admissions,
consultations, cards, counselling or a facilitator app. No new notification type
is required: activity_group audiences use the communication centre's existing
approval, scheduling and private delivery rules. A new client capability selects
activity composition; it does not bypass group authority.


## Phase 3: progress alerts

`progress.band_changed` (academics, learner/parent) and
`progress.staff_band_changed` (academics, teacher/admin) use optional standard
channel preferences. Generic messages and opaque alert IDs contain no learner
names, marks, subjects or clinical values. Fresh canonical evidence and current
recipient authority are rechecked at fanout and final transport. Family uses
frozen released running evidence only. `growth.progress_bands_configured` is an
ID-only transactional event that queues threshold rechecks, not a public notice.


## Early warning

`early_warning` is disabled by default and depends on people and academics.
`growth.risk_rules_configured` is an ID-only outbox event, not a notice type.
Hourly `risk:evaluate` jobs and academic/attendance events observe current-day
canonical evidence. Unavailable wellbeing and discipline sources stay null until
explicit authoritative adapters exist. No family risk labels or clinical-source
counts are exposed. Leadership receives grade/date counts only.


## Discipline

`discipline` defaults off and depends on people/academics. Schools must explicitly
publish a year framework; there is no invented punitive default.
`discipline.framework_published` and `discipline.record_changed` are ID-only
transactional events queuing current risk observations. No private reason or
infraction label enters outbox/audit payloads. Family and grade-head notices
follow with the private API/workflow increment. Confirmed official counts feed
early warning and the aggregate-only leadership adapter; pending, overturned
and unavailable records are distinguished from confirmed history.

## Intake and admissions

The existing `intake` module covers the additive public application form and the
freshly authorised office pipeline. Legacy v1 registration routes remain intact.
Resume/decision emails use encrypted existing mail jobs, with fresh source,
school/module, expiry and contact checks immediately before delivery; an email
link never creates guardian approval. No new public native SDK or paid payment
provider is required. `admissions:maintain` recovers offers every minute; its daily
`--retention` variant erases due private copies even when the module or school is
disabled. New UI keys have English fallbacks in all panel catalogues; fluent
Afrikaans/isiZulu review remains separately unqualified.

## Private gallery

`gallery` defaults off and depends on people. Groups are optional sources rather
than permission to browse personal family galleries. The default rule requires
verified guardian tag approval; self-consent requires explicit school configuration.
`gallery:prune-copies` runs daily at03:30, including disabled schools/modules, to
erase retired image variants and old unknown rollback copies inside each school's
private gallery directory. Academic completion invokes a synchronous references-only
transfer event; separate immutable guardian choice controls retaining/copying tags.


## Scoped group conversation notices

`group_chat.staff_update` and `group_chat.family_update` belong to the optional
`groups` module and also require the current `chat` switch/source policy. They use
standard optional channel preferences. Staff notices open the allocated
`/group-conversations` panel or the dedicated staff inbox's existing group reader;
family notices open `/activities/groups`. Payloads contain IDs/revision/persona
and a fixed route, never participant names, thread titles or message snippets.
Fresh source guards precede fanout, transport and inbox counts/selection.
The existing `communication.message` audience broadcasts remain separate.

## Money preparation boundary

The internal ledger adds no public module entitlement, payment method or
notification type. `wallet`, `vendors`, ticketing and financial card spending
remain unavailable until their real domain/API/client capabilities are built.
The existing `fees` module retains historical records read-only; the shared
retention gate also recognizes module-specific ledger balances and posting
history, including emptied wallets when those future modules are registered.
No school's history grants another school's entitlement.

`ledger:reconcile --json` runs nightly at02:20 and reports failures through the
critical structured `money_ledger_reconciliation_failed` signal. Deployed paging
must be connected to the school's existing operations route before real money
uses this kernel. The command's default is read-only; `--rebuild` repairs only
cached projections from valid posted history. Stable financial receipt identity
is permanent, separate from the pruned24-hour HTTP replay cache. Existing notice
routing still rechecks source, current recipient and transport permissions.
