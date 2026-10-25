# Consultation domain

The default-off consultations module adds scoped polls, surveys and nonstatutory
elections. Configuration has revisioned, immutable-operation receipts and encrypted
configuration audit history. Questions/options/candidates freeze on publication;
closing/cancelling requires a reason and cannot reopen a used ballot. UTC instants
roundtrip under school timezones via the shared cast. The minute scheduler opens
or closes due records once and records an opening outbox event; expired scheduled
records close without a late opening. Notification and live adapters follow with
the actual private routes, not placeholder links.

Voting rechecks fresh school/account/password/role/module, source audience,
current guardian/enrolment and learner approval. Facilitators receive only their
actual group staff scope; a real dual Parent/Facilitator retains their independent
own-child scope. Administrative configuration requires explicit consultations-manage,
seeded only to system school admins; custom roles are preserved. Platform identities
and impersonation cannot vote or manage. Readonly preserves authorized reads.

The database enforces one participation per school/consultation/person. Anonymous
responses have random UUIDs, encrypted answers and no identity, operation/hash,
participation reference, sequence or identifying timestamp. Participation has only
school/consultation/user, with no response link or timestamp. Vote paths never enter
actor-linked operation receipts or configuration audit. Duplicate attempts report
that a response is already recorded; callers can confirm participation without
replacing answers or retrieving an anonymous response ID. Validation and simulated
response failure leave neither partial participation nor a partial ballot.

Private results stay hidden until close even for managers. Public-running results
are aggregate-only. Individual text/named responses use a separate manager reader
after close, paged at50; anonymous output has no response ID, timestamp or identity.
Aggregate recount uses a consistent locked consultation and checks participation
and response totals. Composite foreign keys enforce school scope. Retained data
prevents destructive rollback; an empty schema roundtrip preserves other domains.

All17 consultation domain/migration cases pass in the affected combined cohort,
including actual MySQL constraints, encryption, rollback, private results, immutable
configuration, fresh roles/guardian links, readonly, own-group/dual-role scopes and
clock boundaries. The initial future-opening test reproduced naive UTC being read
in the school timezone; adoption of UtcInstant corrected it. A password-refusal
fixture was corrected to force-fill a protected test-only flag. Whole-app Larastan
and explicit Pint pass. The related39 communication engine/role tests also pass
after the shared source-role fix; strict canonical schema parsing and lint pass.

This is the domain foundation, not a completed consultation application. Typed API,
panel authoring/voting/results, shared staff/family readers, rule-driven notices
and actual browser workflows remain the next active slice. No statutory-election
legal sign-off, live delivery, production activation or performance qualification
is claimed. The cancelled pilot campaign is not an acceptance requirement.
