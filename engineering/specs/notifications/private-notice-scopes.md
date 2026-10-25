# Private notice source predicates

`App\Services\Platform\Notifications\PrivateNoticeScope::apply(Builder
$notifications, User $actor): void` adds SQL predicates for one private feature.
Register the literal notification-type prefix in `config/private_notices.php`
under `scopes`. The provider must whitelist supported types and recheck fresh
feature-specific role, source/owner/grant, module, revision and release/expiry
conditions. It must not retrieve private notice/body columns, use app context as
authority, mutate the current school, or materialize IDs after paginating.

`NotificationVisibility::forUser` retains its recipient/legacy-history union,
then applies `PrivateNoticeScopes`. Prefix matching is literal, including
underscores. Each provider is nested below its prefix condition and the outer
recipient/school predicates, so an OR cannot widen another prefix, recipient or
school. Counts, pagination and keyed lookups use the same filtered query. Actor
membership, password change, platform identity, active school and impersonation
are rechecked centrally. Source refusal denies that prefix before a notice body
is loaded; a provider context replacement is refused and the school restored.

The registry starts empty, preserving existing unrelated notice behavior while
features add their concrete query adapters. It does not replace fresh send or
transport-time authorization. Progress, discipline, transfers, recognition and
wellbeing owners register their own source rules in follow-on increments. There
is no new unread or mark-read HTTP endpoint in this shared increment.

The framework and existing notifier cohort passes **25 tests, 116 assertions**,
changed-file Pint and full configured Larastan. Real MySQL tests prove literal
prefix isolation, hostile provider OR grouping, recipient/school isolation,
password/membership/platform/suspended-school/impersonation refusal, context
replacement/restoration and later-context query pinning. The actual Parent inbox
returns source-filtered rows and pagination total. Test source providers qualify
the extension mechanism only; they are not real feature authority adapters.

## Delivery extension

A registered provider may also implement
`PrivateNoticeDelivery::allows(User $actor, string $type, array $payload): bool`.
`PrivateNoticeScopes::permitsDelivery` checks it before Notifier fanout and again
immediately before queued mail/push transport. It reloads the actor and checks
active school/membership, password change and platform exclusions. Replaced
context refuses delivery and restores the caller's school. Existing explicit
transfer/progress/cover guards remain present; SQL-only registrations retain
those existing delivery rules. New private feature providers implement both
interfaces and whitelist their actual type, source revision and fresh grants.

Private staff notification types can be listed in `private_notices.staff_types`
to use the staff application origin for mail. `wellbeing.staff_update` is
reserved there; family replies keep the family origin. Existing staff mail types
are preserved. No caller-provided origin is accepted.

The combined extension/framework/notifier cohort passes **28 tests, 128
assertions**, Pint and complete Larastan, including source withdrawal between
fanout and transport, stale actor/password/platform changes, replaced context,
ordinary-type compatibility and actual queued staff mail URL. These are shared
mechanism tests; concrete feature source-policy qualification remains separate.
