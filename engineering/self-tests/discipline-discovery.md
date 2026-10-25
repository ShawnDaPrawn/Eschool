# Discipline discovery and read-only capabilities

Retained own discipline records require a current dated teaching allocation and learner membership before pagination. Managers and dated grade heads retain their explicit scopes; a counselling role alone does not disclose classroom queues. Configuration returns the current module write capability for discipline and risk, while read-only modules retain authorized reads and refuse mutations.

Regression evidence: two discipline regressions first failed against the preceding source (revoked allocation still affected pagination; writable capability absent). The corrected isolated HTTP cohort passes 17 tests / 536 assertions, including current and ended allocation pagination, counselling-only refusal, and read-only discipline/risk reads with refused writes. Full Larastan and Pint on all four changed PHP files pass.

This change does not grant counselling case access or make read-only modules writable. Client controls must also honor the optional capability; absent capability is treated as read-only for mutations.
