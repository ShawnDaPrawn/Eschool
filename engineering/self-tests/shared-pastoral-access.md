# Shared restricted-access verification

The first role/helper/tenancy cohort passed 15 tests with 512 assertions.
After the empty-registry rollback/reapply test and fresh-install role-order
guard, the final cohort passed **16 tests, 515 assertions**. Changed-file Pint
and the full configured Larastan analysis pass. The legacy/OpenAPI contract cohort passes **217 tests, 1,291 assertions**,
including installed-client shapes and route/spec coverage.

Covered: fresh restricted roles/capabilities; denied school-admin/facilitator
access; cross-school role reuse; ended membership/inactive accounts;
impersonation/global/default contexts; platform staff exclusion despite an
otherwise valid counselling assignment; CLI identity registration without school
grants; custom-role retention; and retained registry rollback refusal. No case
UI or per-case assignment behavior is claimed. Those are consuming features.

Local tests use a dedicated synthetic `eschool_pastoral_test` database. No real
school accounts, platform operators or production settings were changed.

Authorization integration follow-up: the route-wide role matrix explicitly
includes Facilitator, Counsellor and Safeguarding. All **11 matrix cases pass
(27 assertions)**. The tenancy rehearsal now discovers subsequent feature
migrations, rolls children back before their parents and reapplies every feature;
its check passes **1 test, 165 assertions**. This prevents later school-owned
tables from silently escaping rollback coverage. No role/tenancy check is waived.

Role-transition privacy regression: all three new staff profiles reproduced
retained teacher IDs/group access after replacing a Teacher role. AcademicAuthority
now requires a fresh active Teacher membership before using retained teacher
profiles/allocations. Teacher records are retained; they grant no implicit rights.
The transition/route-matrix/sensitive-access/timetable cohort passes **48 tests,
354 assertions**. Explicit leadership and management grants remain separate.

The existing mark-engine regression passes **24 tests, 264 assertions** after
the Teacher-role guard. Changed-file Pint and full configured Larastan pass.
