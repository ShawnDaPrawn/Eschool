# New year: carry the structure, not the people

Action Plan step 1.19 (first part). Product owner decision (29 October 2026): when the year changes, the same groups and activities can exist, but everyone is taken out and assigned afresh, so a Grade 9 learner is never left in last year's Grade 8 team.

## What the new-year wizard carries now

Two new carry-forward choices in `NewYearService::CARRY`, ticked in the wizard's carry step and run only at the switch, like the existing ones:

| Choice | Action | Copies | Never copies |
| --- | --- | --- | --- |
| `teaching_groups` | `TransferTeachingGroups` | each active teaching group's subject, grade, name and homeroom, through `TeachingGroups::save` (audited, outbox event, projections refreshed) | learner memberships, teacher allocations, marks |
| `activity_groups` | `TransferActivityGroups` | each active activity group's tree, name, discipline, season label, season dates and gender, grade and age eligibility, through `ActivityGroups::save` (audited, operation receipt) | members, captains, staff, sessions, attendance, materials |

Rules:

- **Empty.** People are allocated afresh in the new year.
- **Once.** A group the new year already has (teaching: same subject, grade and name; activity: same name under the same parent) is left alone, and the wizard shows the new year's existing count, as for the other choices.
- **Active only.** Archived groups are not carried.
- **Seasons move with the year.** Dates shift by the gap between the two years' start dates and are kept inside the new year and inside the parent group's season.
- **The tree is kept.** Parents are created before children and children point to the new parents. A child whose parent was archived becomes a top-level group.
- **Last year is untouched** and stays readable as history.
- The actor needs academic-structure management for teaching groups and activity-group management for activity groups, checked by the existing services.

## Acceptance tests (`Academics/NewYearStructuresTest`)

| Given | When | Then |
| --- | --- | --- |
| An active and an archived teaching group | carried twice | one copy of the active group, same homeroom, no members |
| A soccer group with an Under 14 boys child and a member | carried | tree, gender and grade rules kept; seasons moved into the new year inside the parent; no members; last year keeps its member |
| An active and an archived activity group | carried twice | one copy of the active group |
| One of each | the wizard's carry options | both offered with their counts |

## Still to do in 1.19 and 1.20

- Houses: keep each learner's house into the new year (membership is per year).
- Bell schedules, rooms and the canonical timetable.
- Grade heads, principal and other appointments, and teaching allocations, as drafts for review.
- Award windows, discipline framework, family fee accounts.
- Re-assignment helpers: teaching groups filled from the new homeroom and subject choices; "last year's members who are still eligible" for each activity group.

## Part two: bells, houses, award windows and discipline

Four more carry-forward choices (`NewYearService::CARRY`):

| Choice | Action | Copies | Never copies |
| --- | --- | --- | --- |
| `bell_schedules` | `TransferBellSchedules` | each bell schedule's name, section, periods and breaks, through `TimetableConfiguration::bells` (audited) | lessons: they name teachers and teaching groups, which are allocated afresh, so the new timetable is built on the copied bells |
| `houses` | `TransferHouseMemberships` | each house membership open on the old year's last day, for a learner who has a homeroom with a grade on the new year's first day and is not marked as leaving that year, into the same house from that day, through `RecognitionCatalog::membership` | points; leavers (including one promoted and then marked as leaving); closed houses; learners in an ungraded homeroom (placed by hand) |
| `award_windows` | `TransferAwardWindows` | each nomination window of an active school or grade award category, as a draft, its dates moved by the gap between the two years' start dates and kept inside the new year, through `RecognitionCatalog::window` | windows of activity-group categories (the school picks the new season's group); nominations and presentations |
| `discipline` | `TransferDisciplineFramework` | the framework's title, levels, sanctions and infractions, published as the new year's revision 1 through `DisciplineFrameworks::save` | records, counters, detentions: every learner starts the year clean |

Rules:

- **Houses keep their people.** They are the exception the Action Plan names: a learner stays in their house.
- **Once.** A new year that already has bell schedules, house memberships, award windows or a framework is left alone. Recognition writes use operation IDs derived from the source row and the new year, so a retried switch cannot add one twice.
- **Modules.** Houses, award windows and discipline are offered only while their module (`house_points`, `awards`, `discipline`) is on. With it off, the choice shows nothing to copy and copies nothing.
- **After the switch.** Discipline changes are written to the current year only, so the framework is copied just after the new year becomes current, in the same transaction (`NewYearService::AFTER_SWITCH`).
- Each action checks the actor's authority first: academic management, recognition management, or discipline management. Each resolves both years within the school, so another school's year is not found and nothing is written in either school.

### Acceptance tests (`Academics/NewYearCarryTest`)

| Given | When | Then |
| --- | --- | --- |
| Asha (promoted) moved from Blue to Red in the year; Ben (leaving) in Red | carried twice | one membership: Asha in Red from the new year's first day; the old year keeps three |
| A closed house; then an ungraded new homeroom | carried | nothing |
| A bell schedule with a period and a break | carried twice | one copy with both, in order |
| A school category window 1 to 31 October, and a group category window | carried | one draft window, 13 October to 12 November 2027 |
| Bells, a house member and a framework | the wizard switches with those choices | each offered with a count; all copied; the framework is the new year's revision 1; the new year is current |
| The house points module off | the wizard's choices | houses show nothing to copy |
| Asha promoted and then marked as leaving on the legacy page | houses carried | nothing |
| A teacher | each action, discipline included | 403; nothing copied |
| School B's year as the new year | each action | not found; no rows change in either school |
