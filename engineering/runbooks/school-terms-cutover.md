# School terms cutover

This release replaces the semester schema and runtime together. The owner
confirmed no installed clients depend on semesters. There is no compatibility
projection, so do not run old and new calendar code against one database during
an ordinary mixed-revision rollout.

For an authorized deployment, take and verify a database backup first. Pause and
drain workers and the scheduler, put the web runtime into maintenance, run the
normal migrations, and deploy the matching web/worker/scheduler revision and
bundled apps together. Verify term/year reads, class-linked allocations and a
calendar edit before resuming normal processing. Use the existing environment /
release runbooks for identity, backups and smoke checks; never reuse production
credentials/data for staging.

The migration keeps calendar IDs, date values, school/year ownership, permission
IDs/grants and academic foreign-key links. It does not guess class grades or
rewrite learner marks. Missing/reversed imported dates remain visible for review.

Before new calendar metadata/templates are used, migration down restores the
original table/column/permission names and foreign-key behaviour with the existing
records intact. Down removes grade bounds, lock/report metadata and templates:
after use, rollback requires the verified backup and a matching code/database
restore plan, not an unreviewed loss of that metadata. Rehearse on an isolated
copy before an authorized production cutover.

Merging the feature is not deployment or pilot acceptance. No cloud resources or
live migration are performed by this implementation. Additional staging/email
spending remains capped at US$10/month.
