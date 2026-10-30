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

## Fill from the homeroom (Action Plan 1.20, first part)

A teaching group for one homeroom can be filled in one step: **Add everyone in the homeroom** on the group page (academic management).
- Each learner enrolled in the homeroom on the chosen start date becomes a member from that date until their enrolment in the homeroom or the year ends, with the chosen mark rule.
- Each membership goes through the same dated-membership path as adding one learner (`TeachingGroups::dated`), so it is checked, audited and projected the same way.
- Learners who are already members over that time are left alone, so it can be run again.
- A group without a homeroom (an elective across homerooms), or a start date outside the year, is refused.

Acceptance tests (`Academics/TeachingGroupFillTest`):
- Asha (all year) and Ben (moves to 7C at the end of June) in 7A; Cara in 7C: filling Maths 7A from 10 February adds Asha until 31 December and Ben until 30 June, not Cara. Running it again adds nothing.
- An elective group, or a 2027 start date: refused.
- The page offers it and fills with the chosen rule. A teacher gets 403 from both the service and the route, and nothing changes.

Still to come in 1.20: caseload renewals for open cases (`risk_caseloads`).

## Activity groups: last year's members (Action Plan 1.20, second part)

On an activity group in the workbench, **In this group last year** lists the learners who were members of last year's group with the same name (under a parent of the same name) on that group's last day. To appear, a learner must also:
- be at the school this year,
- not already be in this group, and
- meet its gender, grade and age rules now (the same check as adding a member).

Each name has an **Add as member** button, which posts the ordinary membership save. Nothing is added by itself, and a closed (read-only) year shows no suggestions. Tests: `Activities/ActivitySuggestionsTest`. A learner who left the group early, left the school, no longer meets a rule or is already a member is not suggested, and a teacher is refused.

## Staff drafts (Action Plan 1.20, third part)

In a new year, a leader drafts who teaches each teaching group and who holds each homeroom, starting from last year's, and applies the draft in one step.

User stories:
- As a deputy setting up next year, I want last year's subject teachers and homeroom teachers filled in for me, so I only change what is different.
- As a teacher, I see nothing of next year's draft until it is applied.

Rules:
- It is a step on the existing **Academic allocations** page (academic management, `academic-structure-manage`): **Draft from last year**, choose the year, **Build the draft**. There is no separate workbench or menu item.
- The draft is for a year that has not ended. Last year is the school year that starts just before it; with no earlier year there is no draft.
- Rows: every active teaching group of the year, and every homeroom with a homeroom allocation last year or this year, or an enrolment this year.
- Prefill: a teaching group carried over from last year (same subject, grade and name, as `TransferTeachingGroups` matches them) gets the teachers who taught last year's group at the end (their allocations ran to its latest end date). A homeroom gets last year's homeroom teachers the same way. Someone who is no longer on the staff is shown as "no longer on the staff" and not drafted. A group that is new this year starts empty.
- A group or homeroom that already has an allocation in the year is shown as already allocated and left out of the draft; it is changed on its own page.
- The leader changes any teacher, removes one (Nobody) or adds one, then **Apply the draft**. Applying writes, for each chosen teacher, an ordinary dated allocation for the whole year (its first to last day, reason "Drafted from last year") through `TeachingGroups::dated`, with its checks, audit, outbox event and projection. One audit row `academics.staff_draft_applied` records the counts.
- The draft is not stored. Before applying, nothing is written, so no teacher can see the year's groups through it.
- The page carries a fingerprint of the draft's allocations (this year's and last year's, and who is on the staff). Applying recomputes it and refuses, writing nothing, if anything changed. Applying the same draft twice therefore writes nothing the second time; the new draft shows those rows as already allocated.
- A row that is not open in the draft (another school's group or homeroom, or one already allocated) and a teacher who is not on this school's staff are refused, with nothing written.
- The form stays within PHP's input limit (`max_input_vars`, 1000 by default): one field per drafted teacher, or a single field for a row with none, and at most 900 such fields per submission (`StaffDrafts::FIELDS_PER_SUBMISSION`). Any later rows are offered once these are applied. The form's last field is a completeness marker. A submission without it, which is what happens when the server drops trailing fields, is refused with nothing applied.

Data: no new table. It reads and writes `teaching_allocations` and `homeroom_allocations`.

Acceptance tests (`Academics/StaffDraftTest`):

| Test | What it shows |
| --- | --- |
| `test_the_draft_prefills_last_years_staff_and_applying_writes_the_new_year` | Maths 7A (Theo until June, then Tara) drafts Tara; Science 7 is new and empty; 7A and 7C draft their homeroom teachers. Nothing is written and a teacher cannot open the group before applying. The edited draft writes 2027 allocations; applying it again is refused and writes nothing. |
| `test_a_teacher_who_left_the_staff_is_shown_but_not_drafted` | Theo is shown for 7C as no longer on the staff, not drafted, and cannot be chosen. |
| `test_the_allocations_page_builds_and_applies_the_draft` | The page offers the step, shows the draft and applies it. |
| `test_a_draft_cut_short_in_transit_is_refused_and_nothing_is_written` | The page sends one field per row here and the marker last. A submission without the marker is refused and writes nothing. |
| `test_a_teacher_cannot_build_or_apply_a_draft` | 403 from the service and both routes; nothing written. |
| `test_another_schools_groups_teachers_and_years_are_refused` | School B's group, teacher, homeroom or year from School A: refused (validation error or 404); neither school gains an allocation. |
| `test_a_draft_built_before_allocations_changed_is_refused` | An allocation made by hand after the draft was built makes applying it fail, through the service and the page. |
| `test_an_ended_year_or_one_without_an_earlier_year_has_no_draft` | A year with no year before it, or one that has ended, has no draft. |

Not in this step:
- Grade heads and principals (`academic_leaderships`) are not drafted; they are appointed on the same page as before.
- The draft is not saved between visits; leaving the page loses edits.
- The new-year wizard does not link to the draft; it is reached from Academic allocations.
- No app or v2 API change.
