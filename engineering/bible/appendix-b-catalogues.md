# Appendix B: module and notification catalogues

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
