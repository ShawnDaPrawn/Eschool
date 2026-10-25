# Live credential lifetime correction

The review found that the explicit expires_at check omitted Sanctum's configured
maximum age. An actual MySQL regression issued a token 59 seconds into a
one-minute lifetime, opened its live lease, advanced two seconds and confirmed
HTTP now refused the same credential. Before the correction the still-live
45-second lease continued resolving its actor: the final assertion failed.

Publication now checks current created_at against sanctum.expiration as well as
explicit expires_at, token existence, scope, purpose, password and fresh actor
policy. A second case disables the global maximum age and proves explicit expiry
still removes a lease. A null configured lifetime does not arbitrarily expire
otherwise valid old tokens. No existing lease gains authority from this change.

Final real MySQL live authority/fanout cohort: 9 tests / 157 assertions passed;
whole-app Larastan and Pint passed. This is a focused correction to the live
transport, separate from subsequent visible group/poll consumers.
