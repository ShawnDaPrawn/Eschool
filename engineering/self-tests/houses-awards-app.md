# Recognition app, annual groups and private notices

The existing learner, parent, teacher and native facilitator homes now link to the
shared recognition reader. Facilitator capabilities come from current server
recognition authority. Repositories bind school/account/token and check them before
and after requests. Session refresh, role/module changes, selected child changes,
backgrounding and disposal clear private views and drafts. Late responses from an
old view cannot replace or erase its successor. Read-only staff retain history.
Unknown point outcomes retain one immutable UUID/body for exact retry.

Automated shared recognition coverage passes18tests, including phone/tablet/desktop
layouts, separate permanent awards and yearly points, exact retry, late responses,
private person invalidations, read-only access and lifecycle erasure. Family entry
checks pass2tests; native facilitator entry checks pass4tests, including private
history and revoked capabilities at390/1280 widths. Existing facilitator checks
also pass. Affected Dart analysis has no errors or warnings; existing style
information is not treated as a functional failure.

Actual Chrome runs pass the complete native facilitator sign-in/history/revocation
flow, and both learner and parent sign-in/own-history/permanent-awards/background/
revoked-access flows. They use synthetic HTTP fixtures through the real app shells,
without Firebase or external recipients. Both new Chrome flows are in CI.

Group-scoped definitions now use an explicit per-category/year group on the existing
award window. Only school managers can change this scope. Current dated group heads
inherit category administration for the mapped group. A child keeps its parent
annual group, and existing nominations/presentations prevent remapping. Reusing the
same definition in2027 preserves its2026 presentation. Four new recognition service
cases plus a transfer source-scope case initially passed5tests/38assertions.
The final recognition/transfer cohort passes74tests/747assertions, with19additional
route authentication cases/152assertions. Whole-app static analysis and Pint pass. The optional generated SDK field is integrated from the coordinated contract
build (1,539 generated files, two byte-identical generations,96 SDK tests pass);
no generated Dart was hand edited.

Recognition notices contain fixed generic text and source references. Current family
relationships and approved learner access are checked before recipient selection,
inbox counts/body retrieval and transport. Void awards, ended guardian links and
disabled modules remove access. Transfer consent notices now also have a SQL source
scope before pagination. Private recognition events carry only kind/source/revision;
they are distinct from aggregate school counters. The shared notifier schedules
fanout after commit, including with the synchronous queue driver.

The real Reverb adapter, home-screen private moment subscription and measured
2second live target still require the shared live-hub integration. Polling and
synthetic invalidations do not establish that latency. Staging/device/fluent-language
review and external mail/scanner/provider acceptance remain separate. No production
or store release is inferred from these checks.

After the inbox/contract integration, real panel qualification passed9 recognition,
transfer and dated teaching workflows. The year switch separately passed its
unchanged full browser assertion after both session persistence and early client
filter-state races were corrected; those changes are independently reviewed in
PR165. Recognition and transfer browser fixtures now preserve the demo calendar.

The fully integrated shared Flutter package passes392tests after consuming the
private wellbeing inbox and regenerated API client. This includes recognition and
existing academic/communication/private-record regressions; it does not substitute
for the pending live-hub latency and external device/provider checks above.
