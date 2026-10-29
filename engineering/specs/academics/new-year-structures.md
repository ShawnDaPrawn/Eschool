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
