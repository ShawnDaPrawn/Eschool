# Dated teaching groups and academic authority

Phase 2 allocation prerequisite for marks, registers, reports and timetables.

## Stories and acceptance

School academic administrators create subject/grade/year teaching groups,
optionally associated with a homeroom, place learners with effective dates and
allocate teachers with effective dates. Membership and allocation changes retain
history. Separate dated homeroom teaching allocations project to class_teachers.
Dated grade-head/principal appointments grant only their recorded grade/year
scope for the later moderation, attendance-correction and report sign-off flows.

Teachers read only groups they are allocated to and cannot edit group structures
or self-appoint leadership. Active future allocations allow preparing curricula;
mark/register writes require an allocation effective on the actual capture date.
Family access is added only through each released learner-facing workflow.
No class-name parsing or inferred grade mapping is permitted. A school-only
class-to-grade mapping form makes existing explicit reference APIs usable.

## Data and services

School-owned teaching_groups, teaching_group_memberships, teaching_allocations,
homeroom_allocations and academic_leaderships retain effective dates and audit
history. Every foreign ID is scoped. Duplicate overlapping assignments in the
same group/person/context are refused atomically, while co-teaching is allowed.
Dates stay within the school year; learners require a matching enrolment/grade
for the relevant membership interval. New records use explicit revisions.

TeachingGroups owns structure and dated mutations; AcademicAuthority supplies
fresh canonical group/leadership checks. SubjectTeacher/ClassTeacher gain hidden
nullable source-allocation IDs. Only current canonical allocations project into
v1; old IDs/history remain, managed projections cannot be manually edited, and
fresh dated query scopes prevent a delayed scheduler from reviving ended access.
Unmanaged legacy assignments remain independent until explicitly replaced; v2
group mark/register authority never falls back to them. Projection refresh is
idempotent and scheduled. No learner marks are copied or reinterpreted.

## UI, contracts and verification

Material 3 structure page with class grade mappings, group list/details, effective
membership/teacher forms and scoped leadership appointments. Thin panel/v2
controllers call the same request/policy/services. Always-on academics, no family
messages merely for a draft allocation. OpenAPI/generated SDK and appendices
change with the implementation.

Test own-school happy paths, wrong roles/schools/groups/users, expired membership,
revoked/future/ended allocation, duplicate date windows, optimistic conflict,
projection stability and historical v1 payloads. Include migration down/up,
contracts, admin/teacher browser accessibility at 1024/1280 and relevant app
checks. External staging/real school acceptance remains separate evidence.

Mixed groups retain exact learner authority through the new group APIs. They do
not create whole-class v1 subject assignments. Bound homeroom assignments keep
stable v1 projections, and term-filtered teacher/chat consumers explicitly accept
current dated assignments. Enrolment changes end incompatible group memberships
with an audit; moving grade never projects access into the new grade's homeroom.
