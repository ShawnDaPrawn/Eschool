# Registered private staff inbox

Dedicated staff profiles need one inbox for registered private feature notices without opening legacy Teacher/Firebase routes. The inbox includes only exact configured staff types with fixed generic text and feature keys. Actual recipient links, selected school, fresh staff setup, module access and each source scope apply in SQL before pagination, unread counts or payload selection. Platform operators, impersonation and family-only roles are denied.

Read markers are idempotent and private. New per-recipient markers use explicit UTC storage; old domain markers keep their original semantics. Optional source adapters bridge existing read state without granting new source access. The wellbeing adapter includes only staff updates and delegates writes to the existing permitted-intent service; reporter replies never enter this staff inbox. Source/role/module withdrawal and context changes are checked again before returning or committing.

Two additive typed API operations provide list and mark-read. No arbitrary bodies, stored message text, source IDs, links or contact details enter responses. Native implementation and contract generation follow tested backend authorization; no live transport acceptance is implied.
