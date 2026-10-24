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
