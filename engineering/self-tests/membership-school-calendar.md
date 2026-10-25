# Membership calendar boundary — 25 October 2026

Membership date eligibility now uses each school's calendar day. The sign-in
service keeps its single joined query scoped to one user, hydrates each school
relation, then filters date-only starts/ends with that school's timezone.
Contextual SchoolMembership::active uses the selected school's timezone. New
role grants and ending stamps also use their target school's date, so correcting
reads cannot delay newly granted roles west of UTC.

The regression freezes the same instant around UTC midnight at a Johannesburg
school and a Los Angeles school, with future starts and expiring roles. All four
new cases failed against the prior implementation. After the fixes, the complete
calendar/school-context/context-contract cohort passes 12 tests / 84 assertions;
full Larastan and Pint pass. Tests verify both sides of midnight, inclusive date
bounds, actor-only one-query loading, hydrated clean models, suspended roles and
schools, unchanged request caching, explicit cache invalidation and mutation
stamps. Existing API/context response shapes are unchanged. No database timezone
tables, new provider or migration are needed.
