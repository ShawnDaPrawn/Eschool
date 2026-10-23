# Invitation lifecycle review — 23 October 2026

Two regression cases first reproduced the defect: a guardian's old-mailbox link
still returned 200 after the email changed, including with a direct bulk edit.
The link was capable of verifying the current mutable address. The fix binds
issuance to a hidden address hash and clears ordinary edited-address verification.
Outstanding links are retired on an email edit and old unbound links require
replacement. Guardian consent remains a separate decision.

The same review covers learner setup links: current explicit guardian rights
must override legacy parent ids, school/membership checks apply before password
changes, and queued invitation bodies require valid recipient authority at
transport. Old queued bodies without that binding are suppressed. Printed codes
retain their existing one-use purpose and accept lifelong student numbers.

Tests cover mailbox edits, a changed-back address, missing binding, ended/non-
approving links, ended memberships, expiry/use while queued, reminders, School B
setup via public secrets, unchanged School A password, migration retirement and
rollback. Local results and CI status are recorded in the feature self-test.
No live messages or production migrations were run.
