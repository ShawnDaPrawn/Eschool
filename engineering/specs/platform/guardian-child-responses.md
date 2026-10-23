# Guardian children in legacy parent responses

The legacy parent sign-in and profile responses use `Parents::children()`, the
same current guardian-link boundary as child-scoped requests. An ended explicit
link overrides old father/mother/guardian columns. A new active explicit link
works without rewriting those columns. Current-year enrolment filtering applies
to the entire authorised child query, including father and mother relationships.

Fee visibility remains independent of academic child access. A guardian without
fee rights receives a neutral zero `is_fee_payment_due` value and is not placed
behind the app's fee warning/paywall by that flag. The actual fee endpoints keep
their existing server-side rights checks. Other child fields and v1 response
shapes are unchanged.

Tests reproduce ended-link exposure, omitted explicit links, the ungrouped
legacy OR/current-year filter and fee-right leakage before the fix. Verify both
responses, wrong-school/role, existing approval policies, portal sign-in and all
v1/v2 response snapshots. This changes no approval rollout flag or production
configuration.
