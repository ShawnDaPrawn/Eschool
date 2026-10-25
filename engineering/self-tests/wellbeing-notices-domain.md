# Generic private wellbeing notice delivery

The server records per-recipient opaque notice intents with private source events
and relays intent UUIDs through the transactional outbox. Report changes notify
current incident-team members; case/appointment changes notify independently dated
case assignees. Only a named learner/Parent reporter can receive a reporter-visible
school reply. Anonymous reports acquire no reporter account or reporter-directed
notice, and internal replies never notify the reporter. Actor self-notices are
suppressed when that actor was already explicitly identified in the source event.

All generic text, push/mail payload and outbox metadata omit names, category,
urgency, body, photos, source report/case IDs, private codes and clinical counts.
The implemented staff/family routes are used; staff email stays on the staff
origin. Channels follow school rules and personal preferences. Fresh source
scope is checked before fanout and final transport, and private inbox SQL applies
it before counts/pagination. Role/password/platform/impersonation, dated team or
case assignments, guardian links, enrolment, learner approval and module changes
remove retained visibility/delivery. Read-only preserves authorized reads.

Evidence: 22 focused MySQL/migration/shared private-scope/delivery cases with
134 assertions pass, then the full affected wellbeing/rules/v2-contract/tenancy
cohort passes 465 tests with 3,124 assertions. Pint and whole-app Larastan pass.
Actual typed outbox dispatch, one-time fanout and duplicate suppression are tested,
as are source rollback, physical FK school isolation and retained migration guards.
The MySQL empty rollback initially exposed replacement of an implicit school-FK
index by a new composite index; down now restores that supporting index before
removal, and full down/up/backfill rehearsal passes.

The broad run also corrected the inherited public-report CSP fixture for the
already-implemented data-only photo previews (#156); actual policy was unchanged.
The initial positive-mail test used dotted config lookup for a catalogue key that
itself contains dots; setting the complete configuration map fixed the fixture.
No live push/email send, deployed queue throughput or latency is claimed.

This PR implements the server notice workflow and existing inbox visibility.
The dedicated Facilitator profile has no legacy Teacher/Firebase fallback;
its actual private inbox API/shared screen is the next active integration slice.
No claim is made that this domain slice alone completes that native inbox.

The published stack also inherits the later recognition privacy correction (#159)
and dated discipline/Risk capability correction (#160). Its final merged
wellbeing/recognition/Risk/discipline cohort passes 71 tests / 945 assertions and
whole-app Larastan passes. Two concurrent PHP images exhausted workspace disk
near the end; both exited, source files remained intact, and the static run
completed successfully. Subsequent runtimes are serialized until space allows.
