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
