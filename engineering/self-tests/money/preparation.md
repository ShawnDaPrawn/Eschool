# Phase 3/4 handover and money preparation

This is the bounded preparation pass accepted on 26 October 2026. It completes
that source scope, not all of Phase 5. The final delivery PR and its exact-head
workflows are the hosted verification record; no production release, provider
activation or physical-device acceptance is inferred from a merge.

## Executed local evidence

| Cohort | Result and scope |
| --- | --- |
| Final money/access/translation cohort | 25 tests passed, 14,536 assertions (including translation catalogue assertions). Twenty kernel tests cover integer balance/currency, permanent key/source replay, direct SQL immutability/invariants, owner/school/context refusal, disabled/package-removed wallet history, suspension, projection reconciliation, independent-process concurrent spends/retries, and four real two-connection stale snapshots. Two approved-unpaid access/privacy tests and three existing translation checks complete the cohort. |
| Module independence follow-up | 34 tests / 294 assertions passed: the current 21 kernel tests plus 13 module-control tests, including wallet cash funding with fees off, immutable account-module ownership and disabled-switch refusal. Cohorts overlap and are not summed. |
| Joined/backend handover cohort | 108 tests / 1,683 assertions passed across admissions, approval, module controls, table tenancy, outbox, live publication, notifier and queued delivery. This includes the three new admission→guardian→enrolment/transfer/receipt stories. |
| Learner route widgets | 35 routing/widget tests passed, including both saved fee-lock redirects and the real route when the fees module is off. |
| Family app analysis | No errors or warnings; existing informational lints remain. |
| Money static analysis | Larastan level 5 passed, no baseline or suppressions for the five kernel/command/migration files. |
| Repository PHP static analysis | Existing level 1 configuration passed; the repository-wide baseline was not expanded. |
| Documentation | Docusaurus production build passed after correcting the overdue-fee guide. |
| Formatting/workflow | Selected PHP Pint, Dart formatting, actionlint and whitespace checks passed. |
| Scoped source/security review | [Manual review and its tooling limits](../../security/p5-preparation.md); not an independent money-flow assessment. |

Early runs corrected test fixtures and the isolated migration-test teardown.
No production history-deletion guard, guardian privacy check or required CI
aggregate was weakened. The kernel review caught stale outer repeatable-read
history/module/package authority; current locked reads
now pass all four two-connection cases. Actual independent-process simultaneous
spends left one refusal and the correct balance; simultaneous same-key retries
returned the same entry.

The shared recovery fixes have three real RED→GREEN proofs: a delayed direct
job bypassing exhausted-event parking, a Honolulu-origin backoff retried early
by UTC, and UTC backoff retried early in Kiritimati. See
[the recovery handover](../../runbooks/phase-3-4-recovery.md) and
[the joined admissions evidence](../p3-money-handover.md).

Private local logs are under `/workspace/scratch/`: `pre-money-final-money-tests.log`,
`pre-money-handover-cohort.log`, `arrears-route-test.log`,
`pre-money-family-analyze.log`, `pre-money-static.log`,
`pre-money-global-static.log` and `pre-money-pint.log`. They are execution artifacts,
not repository content or proof that external acceptance ran.

## Newly admitted community identity

A separate composed case creates a real application/resume/offer/enrolment,
accepts guardian and learner invitations, and proves explicit approval is still
needed for groups and cards even on the legacy login setting. It issues/scans a
real synthetic QR credential, prepares learner and guardian audience notices,
and checks inbox and queued-mail authority. A school switch and approval
withdrawal revoke group/card access, exact scan replay and both notice personas.
The only new product fix is a reproduced optional-mobile enrolment failure:
nullable contact/User data stay null while the legacy required Parents column
receives an empty value. No phone number is invented and no input becomes
mandatory. The agent's isolated cohort passed33tests/266assertions; final combined
admissions verification is recorded on the delivery PR. No physical card/scanner
or live delivery is claimed. Test scanner/queue fakes are explicit in the case.

## Client and integration boundaries

The retired lock alias also bypasses the fees module gate solely to return Home;
financial screens retain their own module checks. The existing learner and
child-switch smoke targets now use actual unpaid
profiles and compulsory mode enabled. They exercise fresh sign-in, returning
persisted learner startup, Parent academic child opening and child switching.
Those targets run in the existing hosted Chrome and Android jobs, including the
unchanged required Android family aggregate. Native Gradle/Maven caching and
bounded dependency-only retries are qualified by that hosted run, not claimed as
an extra local Android execution. No behavioral test failure is automatically
retried or waived.

Current Phase 3/4 state/error/retry screens were reviewed at their shared routes
and independently qualified feature flows. The refinement stays bounded: joined
backend tests and the navigation correction supplement that coverage; it does
not claim a new complete four-profile browser/device sweep. No new wallet,
vendor or payment UI exists in this foundation. Future scoped vendor access,
online financial card authorization, source cutover and per-school history
preservation are explicit [contracts](../../specs/money/ledger-foundation.md).

## Parent overdue-reminder layout regression

The first delivery head's strict Chrome child-switch job failed. A local run
reproduced the same failure. A temporary diagnostic handler, which called the
original error handler, captured two actual RenderFlex overflows of8.8pixels
at the Parent child-card Column. The fixed160px card could not contain the
additional informational reminder.

Child cards now have a minimum height and grow with content, with padding for
the existing bottom arrow. Reminder text has no false link underline. The
original child-switch test was restored without diagnostic/error suppression,
and its complete real Chrome flow passed. The ordinary framework error handling
and all child selection/request assertions remain intact. No test was skipped
or reclassified as a transport retry. Logs: `pre-money-child-browser-red.log`,
`pre-money-child-browser-red-errors.json`, `pre-money-child-browser-green.log`.
Hosted Chrome and Android jobs must pass on the final revision before merge.

## External evidence and subsequent slices

Staging restore/recovery, deployed alert routing, real delivery, fluent wording
review, physical cards/readers/phones and iOS/store qualification remain tracked
separately. Live gateway checkout/signatures/replay, cash/bank/statement observation,
financial policy choices and the independent money-flow security assessment belong
to their later implementation/release slices. The raw Bible and private execution
logs were not published. No legacy financial data/floats were dropped, no opening
balances imported, and no paid provider or production flow was activated.
