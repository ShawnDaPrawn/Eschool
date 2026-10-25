# Wellbeing domain verification — 25 October 2026

This slice implements the protected domain, not the complete public/app workflow.
The wellbeing module remains disabled by default. Public/panel/facilitator views,
media and notifications continue in follow-up implementation; no placeholder
screen or unscanned attachment is presented as a finished feature.

Actual automated evidence:

- Broader domain, tenancy, role, module, migration-rehearsal and existing v2
  contract suite: 284 tests / 2,249 assertions passed.
- Expanded final wellbeing domain: 14 tests / 95 assertions passed.
- Empty rollback/reapply and retained-history refusal: 2 tests / 10 assertions.
- Full Larastan: zero errors; changed PHP formatted with Pint; whitespace clean.

The domain tests exercise anonymous storage and exact submission/follow-up retry,
keyed secret lookup, wrong secret/school, plaintext/ciphertext checks, emergency
guidance, explicit identification consent, fresh guardian withdrawal, internal
triage notes hidden from reporters, cached-role/assignment revocation before
private entry queries, platform identity and impersonation/global-context denial,
separate team/case grants, configuration metadata without case bodies, encrypted
immutable case corrections, source-link double authorization, large encrypted
receipt replay, appointment boundaries/overlap/adjacency and school-role dates,
revocation-safe assignment cleanup with encrypted history, safe system-role seed,
and retained close/reopen history. No clinician requires a Teacher record.

An identified report's private code alone stops granting access: reads and
follow-up require its freshly authorized named reporter. Anonymous submissions
ignore an authenticated cookie/actor instead of silently identifying the person.
Secrets stay out of URLs and database/audit fields. Ingress/request logging,
physical devices, live emergency response, scanner/media qualification, staging,
acceptance and release are external checks; none is inferred from these tests.
