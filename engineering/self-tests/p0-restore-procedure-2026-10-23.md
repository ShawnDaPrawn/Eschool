# Restore procedure review — 23 October 2026

The [runbook](../runbooks/restore.md) now quarantines before copying production
data, keeps background consumers stopped through inspection, and restores demo
data before resuming ordinary services. It corrects minimum-instance semantics,
clears tagged revisions, uses the array mailer, removes queued delivery intent
and applies the additional US$10/month ceiling to recovery costs too.

Evidence collected locally:

- Reviewed Google's current manual-scaling documentation: zero disables a
  service; tagged revisions are an exception. The CLI reference documents
  `update-traffic --clear-tags`. No cloud command was executed.
- Every shell example parsed with `bash -n` after replacing example placeholders.
- Created a separate local MariaDB database with the current migrations and two
  demo schools. In a transaction, seeded synthetic device/session, queued job,
  pending/completed outbox and invitation state; ran the runbook's SQL verbatim.
- All nine assertions passed: people and learner counts unchanged; device/
  remember credentials cleared; jobs/pending outbox cleared; completed event
  retained; mail set to array; guardian and learner email authority retired.
  The transaction was rolled back. Only fake/demo data was involved.
- `git diff --check` passed.

This proves syntax and local sanitisation behaviour. It does not prove Cloud Run
shutdown, access restrictions, cloud backup restoration, provider isolation,
worker observation, cost feasibility or owner acceptance. Those remain pending
cloud credentials and an approved operation within the existing spend ceiling.
Development and unrelated safe merges continue meanwhile.
