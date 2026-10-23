# Appendix B: module and notification catalogues

The modules and notification types NavigateEd's registries hold, phase by phase. The registries in code will be `admin-panel/config/modules.php` and `admin-panel/config/notification_types.php`, both arriving in Phase 1. This page lists only what exists in the code; the planned catalogues are kept in the private Build Bible (Appendix B) and entries are added here when a phase builds them.

## Phase 1 module registry

Implemented entries: `school`, `people`, `academics`, `communication` (required),
`chat`, `online_exams`, `fees`, `cards`, `intake`, `web_learner`, `web_parent`
and `web_teacher`. Chat, online exams and portals default on. Explicitly
packaged new schools start fees, cards and intake off; upgraded schools keep
their seed features. Fees with retained records become read-only when disabled.
Package entitlement is separate from a school's switch. The notification-type
registry remains outstanding.

## Phase 0

Phase 0 added no module and no notification type: `config/modules.php` and `config/notification_types.php` arrive in Phase 1. The web builds of both apps shipped without a switch; `web_<portal>` (one switch per portal, default on) is the first entry of Phase 1's module registry. The queued mail and push jobs of Phase 0 (`SendMail`, `SendPush`) carry the existing notifications unchanged and record each attempt in `notification_deliveries`; the notification types and their rules attach to them in Phase 1.
