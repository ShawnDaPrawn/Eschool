# Existing profile health access — 25 October 2026

A globally registered active platform identity could also hold a valid school
Parent or Super Admin role. The existing generic profile API then returned
medical alerts/allergies through its ordinary school/guardian field policy,
bypassing the stricter medical feature boundary.

ProfilePolicy now excludes the Health category and locked medical keys for active
platform identities, impersonation and across-school contexts before profile values
or files are loaded. School/guardian fields and ordinary identity fields retain
their existing behavior. The same field policy protects read, write and export.

The independent legacy HTTP regression proved RED: the old policy returned a real
decrypted synthetic medical value to the registered platform identity. After the
fix, the full existing ProfileFormsTest cohort passes 11 tests/47 assertions:
medical read/write refused, ordinary identity fields preserved, original medical
value intact and legitimate school access restored when the exclusion is removed.
An affected profile/role/tenancy/domain cohort also passed 66 tests/1,032 assertions;
whole-app static analysis and Pint pass. No test expectation was weakened.

This patch has no dependency on new family consent tables, services, modules or
endpoints. It does not claim new medical UI or clinical access for school admins.
