# Dated enrolments and lifelong student numbers

One platform identifier belongs to a human, not to a school. It is allocated
under a user lock and a locked global counter: first enrolment year (YY),
seven sequence digits and a Luhn check digit. A school's learner row carries
a compatibility projection of the same number. Old API model output hides
that new column; v2 resources add it explicitly.

An enrolment records learner, school, year, homeroom, start, end, reason and
previous entry. Moving homeroom closes the covering interval on the previous
day and appends a new one. Existing and scheduled later entries remain.
Identical requests are idempotent; conflicting changes on the same date return
409. Wrong-school learner, homeroom and year IDs return 404. Date queries use
the enrolment valid on that date, not the latest homeroom.

`student_sessions` remains a physical compatibility projection for old writes
and installed clients. Legacy model writes append enrolment history. The
projection is refreshed from the entry valid today (or the appropriate year's
boundary outside its dates). Scheduled moves do not change today's homeroom.

The resumable `people:backfill-identities --queue` command processes schools
in worker jobs. It imports known year boundaries from the old session rows,
marks their dates as legacy-derived and never invents intra-year moves.
Run it on staging and rehearse against protected production-copy data before
the production migration is accepted. Local demo verification is separate.

The legacy permanent-cleanup action is transactional. Dated enrolments block
it before academic records can be removed; use archiving and the retention
process. Removing a history-free school record ends only that school's learner
role. Other schools and other roles keep their account. An account with no
remaining memberships is soft-deleted, its sessions revoked, and its lifelong
number retained permanently. This change authorises no production deletion.

The staff v2 history endpoint supports date filtering and pagination, and
requires office permissions. Parent context lists authorised children across
the parent's active schools in a fixed number of queries. New child identity
fields are optional in the app contract; cached v1 child records still round-trip.
