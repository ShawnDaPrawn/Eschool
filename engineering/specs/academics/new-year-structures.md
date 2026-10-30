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
- **Once.** A teaching group the new year already has (same subject, grade and name) is left alone; activity groups are copied only into a new year that has none yet. The wizard shows the new year's existing count, as for the other choices.
- **Each group on its own.** Every active activity group gets its own copy, even two siblings with the same name (a summer and a winter squad), and its children follow their own parent's copy.
- **Active only.** Archived groups are not carried.
- **Seasons move with the year.** Dates shift by the gap between the two years' start dates and are kept inside the new year and inside the parent group's season.
- **The tree is kept.** Parents are created before children and children point to the new parents. A child whose parent was archived becomes a top-level group.
- **Last year is untouched** and stays readable as history.
- The actor needs academic-structure management for teaching groups and activity-group management for activity groups, checked by the existing services. A switch that ticks a choice the actor may not carry is refused as a whole: nothing is copied and the year does not change.

## Acceptance tests (`Academics/NewYearStructuresTest`)

| Given | When | Then |
| --- | --- | --- |
| An active and an archived teaching group | carried twice | one copy of the active group, same homeroom, no members |
| A soccer group with an Under 14 boys child and a member | carried | tree, gender and grade rules kept; seasons moved into the new year inside the parent; no members; last year keeps its member |
| An active and an archived activity group | carried twice | one copy of the active group |
| One of each | the wizard's carry options | both offered with their counts |
| Two same-named sibling activity groups, one with a child | carried | each gets its own copy; the child follows its own parent |
| A teacher without either management permission | carries either kind | refused (403); nothing copied |
| A year planner who may manage teaching groups but not activity groups | switches with both ticked | refused (403); no teaching groups copied, year unchanged, School B untouched |
| School A's wizard | switches to School B's year | refused; nothing copied in either school; year unchanged |

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
| `houses` | `TransferHouseMemberships` | each house membership open on the old year's last day, for a learner who has a homeroom with a grade on the new year's first day, into the same house from that day, through `RecognitionCatalog::membership` | points; leavers; closed houses; learners in an ungraded homeroom (placed by hand) |
| `award_windows` | `TransferAwardWindows` | each nomination window of an active school or grade award category, as a draft, its dates moved by the gap between the two years' start dates and kept inside the new year, through `RecognitionCatalog::window` | windows of activity-group categories (the school picks the new season's group); nominations and presentations |
| `discipline` | `TransferDisciplineFramework` | the framework's title, levels, sanctions and infractions, published as the new year's revision 1 through `DisciplineFrameworks::save` | records, counters, detentions: every learner starts the year clean |

Rules:

- **Houses keep their people.** They are the exception the Action Plan names: a learner stays in their house.
- **Once.** A new year that already has bell schedules, house memberships, award windows or a framework is left alone. Recognition writes use operation IDs derived from the source row and the new year, so a retried switch cannot add one twice.
- **Modules.** Houses, award windows and discipline are offered only while their module (`house_points`, `awards`, `discipline`) is on. With it off, the choice shows nothing to copy and copies nothing.
- **After the switch.** Discipline changes are written to the current year only, so the framework is copied just after the new year becomes current, in the same transaction (`NewYearService::AFTER_SWITCH`).
- Each action checks the actor's authority through its own service: academic management, recognition management, or discipline management.

### Acceptance tests (`Academics/NewYearCarryTest`)

| Given | When | Then |
| --- | --- | --- |
| Asha (promoted) moved from Blue to Red in the year; Ben (leaving) in Red | carried twice | one membership: Asha in Red from the new year's first day; the old year keeps three |
| A closed house; then an ungraded new homeroom | carried | nothing |
| A bell schedule with a period and a break | carried twice | one copy with both, in order |
| A school category window 1 to 31 October, and a group category window | carried | one draft window, 13 October to 12 November 2027 |
| Bells, a house member and a framework | the wizard switches with those choices | each offered with a count; all copied; the framework is the new year's revision 1; the new year is current |
| The house points module off | the wizard's choices | houses show nothing to copy |
| A teacher | each action | 403; nothing copied |
