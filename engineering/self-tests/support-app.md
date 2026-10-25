# Restricted support staff apps

The Teacher app opens support through the current-school module guard. The
Facilitator flavour opens the same shared screens only when fresh server features
allow support. Neither shell infers clinical authority from the app identity.
Professional plans and immutable history, principal caseload appointments, and
approved named teacher instructions have separate views and queries.

Each reader pins account, school and bearer identity and a captured session
generation. Permission refresh replaces the reader even if those identities do
not change. Backgrounding hides private content; resuming rechecks access before
showing it. Withdrawal clears old content. Drafts remain in memory only. Unknown
write outcomes retain the exact operation UUID and immutable payload for retry;
conflicts require comparison before a new operation. Adding a review preserves a
separate unsaved plan edit. Read-only professionals can browse saved plans and
history while mutation controls remain disabled.

Actual Chrome verification passed using the real Facilitator entrypoint and
synthetic HTTP fixtures: counsellor sign-in, create a SIAS plan, expected account
and school headers on every support call, no legacy Teacher auth box, refresh
after caseload withdrawal, and no former private body in the reopened view. The
flow is included in the CI Chrome matrix with the Facilitator build identity.
It uses no real records or external provider calls. Native iOS, device-specific
checks and fluent translation review remain separate acceptance evidence.

The focused shared suite passes 16 tests for three widths, principal/teacher
redaction, read-only browsing, exact retry, preserved drafts, backgrounding,
late replies and session-generation invalidation. Three app-entry tests cover
the guarded direct route, panel URL alias and actual Facilitator shell workflow.
The panel picker follow-up passes six tests/79 assertions including more than
50 eligible teachers, preserved case on search/paging and withdrawal of a draft
before a historical-year refusal. Five API tests/177 assertions also pass.

Review follow-up: appointment choices now remain selected across independent
search/pages and are revalidated by exact eligible IDs. A successful review
reconciles unchanged plan fields and its exact revision while retaining authored
edits; later concurrent writers still require comparison. Both widget regressions
failed against the previous screen and pass after the correction. Private writes
refuse URL query input. The inherited shared clinical guard also refuses pending
password setup. The affected PHP suite passes24 tests/320 assertions and full
configured static analysis is clean. Selected-ID filters add only two optional
query parameters and six generated client lines, with no response changes.
