# Mastery rollback dependency rehearsal

CI caught a stale test sequence: the empty projection rehearsal dropped practice
versions while current Tutor checkpoint/session foreign keys still referenced
those versions and mastery topics. The same real foreign-key error was reproduced
locally before the change.

The rehearsal now rolls dependent projections down in reverse migration order,
round-trips mastery, then reapplies dependencies in forward order. It preserves
foreign-key enforcement and retained-history refusal guards. Explicit assertions
check practice, Tutor checkpoints and the Tutor topic column after reapplication.
The optional peer migration is included when present, since it references mastery.

The affected Mastery/Tutor/practice cohort passes: 46 tests, 533 assertions.
Exact-head CI remains required; this corrects the fixture's invalid rollback
sequence without weakening production relationships or history retention.
