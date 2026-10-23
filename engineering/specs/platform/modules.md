# School module availability

`config/modules.php` registers implemented features and their dependencies.
School setup, people/approval, academics and communication stay on. New
modules enter this registry when their code arrives; later-phase feature names
are not accepted as runnable capabilities.

`school_modules` records each school's package entitlement, enabled/read-only
state and audited actor/time. Package configuration is platform-owned; school
admins cannot grant themselves an entitlement. Unknown packages and module
keys fail closed. Dependency cycles fail closed. A dependency cannot be
disabled while an enabled dependent needs it.

Upgraded schools keep existing chat, online exams, fees, cards and intake.
Explicitly packaged new schools start fees, cards and intake off. A school
admin changes included switches under Settings → School modules. Required
features cannot be disabled, even by a crafted request. Changes affect only
the selected school.

Disabling fees preserves financial records and makes the module read-only
whenever charges, receipts or transactions exist. This is conservative: even
settled history remains readable. New fee writes and the seed's GET reminder
action are denied. Payment-provider settlement webhooks retain their existing
authenticated processing, so an in-flight settlement is not lost.

`ModuleGate`, its facade, `module:` middleware and `@module` use the same
state. The controller/method registry attaches the gate to panel and legacy
API routes, including direct URLs. Navigation and search hide disabled
features. Portal switches affect browser sessions; native app access follows
the underlying feature switches independently.

Context adds configured modules and `feature_flags.module_switches`. New apps
use that marker to distinguish configured availability from older servers'
empty placeholder list. Shared visibility and route guards hide unavailable
navigation, refuse direct links before building the feature, and remove
write actions for read-only fees. The server still enforces every request.

Each switch records `context.changed` in the transactional outbox. Its listener
queues a silent invalidation to that school's topics. Native apps refresh
context on receipt; browser apps refresh on resume. Push delivery and real
portal/device behaviour still need staging evidence.
