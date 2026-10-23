# Academic references and explicit class mapping

Phase 2, foundation of feature 2.2. Owner role: Backend.

## User stories

School administrators need an explicit R–12 grade/phase mapping for each existing
class without renaming it. Teachers, parents and learners need a consistent
read-only grade and seven-point rating reference for future academic screens.
Installed clients keep their existing grading and semester behaviour.

## Acceptance tests

Authenticated active school members can read the complete reference. A school
administrator with class-list or class-edit can read their class mapping; only
class-edit can set or clear it. Repeated identical writes produce no extra audit.
Teacher, parent and learner writes without permission are refused. Foreign-school,
unknown and deleted classes return 404 to authorized callers. Expired membership,
inactive accounts and a role cached from another school grant no authority.
Invalid/missing grade IDs are rejected; explicit null clears the mapping. A
request cannot override school or other class attributes. Existing classes start
unmapped; no class name is guessed. Every basis-point value 0–10000 has exactly
one rating, including all boundary values. Legacy grades/results remain intact.

## Data changes

Global read-only reference tables grade_levels and academic_rating_levels contain
no person or school data. Each has timestamps; grade codes and rating levels are
unique. Classes gain an indexed nullable grade_level_id foreign key (restrict on
reference deletion). The class remains school-owned under BelongsToSchool.
Migration rollback removes the mapping column before reference tables; rollback
necessarily discards new mappings but preserves existing class/mark data. No
legacy grade rows or existing result calculations are migrated or overwritten.

## Endpoints

GET /api/v2/academics/reference returns grades and rating_scale.
GET /api/v2/academics/classes/{class}/grade-level returns class_id, grade_level_id
and grade_level (nullable). PUT at the same path accepts required grade_level_id
(integer reference ID or explicit null) and returns that mapping. All use
Sanctum, school.context, role.any, learner.approved and module:academics;
class operations additionally enforce fresh school-scoped permissions before
idempotent replay and again through their FormRequest and transactional service.
Writes accept Idempotency-Key. All responses use the v2 envelope and no-store
headers. OpenAPI, contract scenarios and generated Dart SDK change together.

## Screens

None in this backend foundation. Existing screens keep functioning; no user-facing
mapping screen or new mark calculation is claimed. Client work follows separately.

## Notification types

None. Mapping changes are audited, not sent to families.

## Module switch

academics is an existing always-on core module. Module middleware enforces its
registered availability; this feature creates no new entitlement or switch.

## Security notes

Reference data are intentionally global and cannot be edited via these endpoints.
Class IDs are resolved only through the current school's scope. Authorization is
checked before lookup and repeated inside a locked transactional write. Validation
occurs after authorization. Only the explicit grade field is written. Audit records
school, actor, class and before/after reference ID; identical writes are no-ops.
No allocation grants a teacher class-edit implicitly. Mapping belongs to the
school class, not to a person; class ownership and permission are tested. Existing
v1 class serialization hides the new internal mapping column.

## Self-test steps

Run academic reference/policy/boundary tests, OpenAPI contract scenarios, route
isolation/authorization inventory and the migration reverse/forward rehearsal.
Regenerate and analyze/test the SDK. Record full CI results separately. Staging
admin mapping, denied teacher/parent/learner writes and installed-client smoke
remain pending until an isolated staging connection and devices are available.
