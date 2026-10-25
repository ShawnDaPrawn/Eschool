# Restricted pastoral access shared by Phases 3 and 4

## Contract

Before loading counselling notes, support cases or other restricted content, a
domain policy calls `SensitiveRecordsAccess::actor($actor, $permission)`. The
default permission is `pastoral-sensitive-access`. This is a prerequisite, not
authorization to read every case: the domain must then check current assignment,
record ownership, the module entitlement and any explicit safeguarding-team scope. It must apply the
same rules to files, lists, exports, notifications and background work.

The helper requires an explicitly selected school, an active account and a fresh
active school membership as Counsellor or Safeguarding plus the named permission.
It refuses impersonation, across-school/default contexts and a registered active
platform staff identity, even when that person also holds a real school counselling
role. School Super Admin, teacher, guardian and facilitator permissions grant no
implicit access. A school administrator with an explicitly appointed restricted
role still needs the domain's case assignment; generic administrative access
must not substitute for it.

## Data, roles and operator boundary

`platform_staff_identities` is global, keyed uniquely by user, with an active flag
and audited changes. It is an exclusion identity, not a school role or a grant of
platform capabilities. Only the trusted operator console registers/revokes it:
`php artisan platform:staff-identity <user_id> [--revoke]`. No public/admin HTTP
endpoint or mass-assignable user flag is introduced. Existing platform operators
must be registered before enabling restricted features. Accounts without school
membership already fail independently. Registering an operator grants no roles.

System Counsellor and Safeguarding roles receive only the base restricted
permission. Facilitator starts with none. Super Admin receives activity-group
management but no restricted permission. Existing custom role configurations
are retained, including names that collide with a newly introduced system role;
they require explicit reviewed permission assignment. No users are auto-appointed. AcademicAuthority also requires a current Teacher
membership before retained teacher profiles or allocations can supply teaching
rights; a role conversion preserves history without preserving implicit access.

The additive migration upgrades existing school role catalogues; fresh installs
retain the original role creation order. Authorization rows are not removed by
rollback. An empty exclusion registry can roll back/reapply; retained exclusions
refuse destructive rollback. No existing academic or transcription data changes.

## Verification and boundaries

Tests cover explicit allow, wrong role/school, current membership and account
revocation, impersonation/global contexts, platform identity overriding a valid
school role, least-privilege role seeds, custom-role preservation, trusted CLI
registration/revocation and rollback retention. Tenancy schema/model checks and
existing role tests run alongside these cases. Domain case-assignment and
record-owner tests belong to each consuming feature; this helper exposes no UI
or record endpoint. External safeguarding/security acceptance is separate.
