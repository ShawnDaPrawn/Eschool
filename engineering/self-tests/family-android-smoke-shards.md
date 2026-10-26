# Bounded Family Android smoke parts — 26 October2026

Hosted CI run40327214256, Family Android job121590296354, was cancelled after
the required job's45-minute budget. Its check-run annotation explicitly says
**“The job has exceeded the maximum execution time of45m0s”**. The job ran from
13:13:59Z to14:00:08Z, including cancellation cleanup. Its log records38 passing
individual cases and no failed test assertion; the last completed target was
the family timetable, followed by an interrupted Gradle build. An unchanged
rerun would retain the same oversized serial target list.

The workflow now alternates the same sorted Family target discovery over two
emulator jobs, each retaining the existing45-minute budget. Every Family
runner creates both manifests, requires both to be nonempty, compares their
sorted union with the complete original manifest, and checks their intersection
is empty before starting the emulator. Flutter consumes those exact manifest
files. New eligible targets therefore enter a part automatically.

Local YAML/command inventory verification used the actual original discovery
from parent59b06a3c and executed the new workflow's Bash inventory step and both
matrix target-reading commands:

| Inventory | Count |
| --- | ---: |
| Original eligible Family target files |35 |
| Part1 |18 |
| Part2 |17 |
| Union, compared as a multiset |35 |
| Intersection |0 |

The sole existing `family_live_recognition_smoke_test.dart` exclusion is
unchanged. The Teacher and Facilitator matrix rows, commands and rendered check
names remain unchanged. The original exact Family check name
`Flutter smoke flows on Android (e-school family)` remains an `if: always()`
aggregate depending on the complete Android matrix. It passes only for a
successful matrix; local execution proves failure for failure, cancellation,
skipping, timeout and missing-result states. Partial completion cannot become
green through the aggregate.

Validation: Actionlint1.7.12 passes; the workflow parses as YAML; all35 original
target paths are preserved exactly with a disjoint balanced partition; both
Teacher/Facilitator row dictionaries compare equal to the original; the
45-minute budget and `fail-fast: false` are unchanged; aggregate positive and
negative shell cases pass; whitespace checks pass. Full target inventory and
state-case output are recorded in `family-android-shards-verification.log`.

No Android emulator was available for a local rerun. Both actual hosted Family
parts and their aggregate remain pending against this patch. No target,
assertion, permission or required check was removed or waived.
