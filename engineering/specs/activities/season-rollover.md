# Season rollover

Action Plan step 1.22. Owner role: Backend and Admin UX. It reuses the year-end carry of activity groups (1.19, `TransferActivityGroups`) within a year.

## User stories

- As a sports coordinator, when summer sport ends I start the winter season in one step: the summer teams become winter teams with the same structure and rules, empty, and I pick the players again.
- As a school, last season's teams, members and attendance stay as they were.

## Rules

- **Source.** Every active group in the current year whose season label is the chosen one ("Summer"). The new season needs a different name ("Winter") and dates inside the year.
- **Copy.** Each group becomes a new group in the new season, through `ActivityGroups::save`, which is audited and checked by `CommunityAuthority`. It keeps its name, sport or activity, gender and grade or age rules, and its place in the tree:
  - a child of a copied group goes under the copy;
  - a child of a group outside the season (a whole-year club) stays under that group.
- **Dates.** The new season's dates, kept inside the parent's season. A group that cannot fit (its parent's season ends before the new one starts) is skipped, and so are its children.
- **Nobody is carried.** Members, captains and staff are chosen again.
- **Once.** A group the new season already has (same name and parent) is left alone. Operation IDs are derived from the source group and the new season name.
- The result says how many groups were started and how many were skipped.

## Acceptance tests (`Activities/ActivitySeasonsTest`)

| Given | When | Then |
| --- | --- | --- |
| A whole-year club containing Summer Soccer (one member) and its U14 Boys (male, grades 7 and 8), and whole-year Chess | roll Summer into Winter, 15 July to 30 November | two created; Winter Soccer under the club with the new dates; Winter U14 Boys under it with the same rules; nobody in them; Summer keeps its member; Chess untouched; again: nothing new; seasons counted |
| A club ending 31 August and one ending 30 June, each with a Summer child | roll | the first child runs 15 July to 31 August; the second (and its children) is skipped |
| The new season named like the old one in another case; ending before it starts; outside the year; an unknown source season | roll | refused; nothing created |
| An administrator; a teacher; School B's year | the page and the action | the page lists seasons and starts one; the teacher gets 403; School B's year is not found |

## Endpoints (panel)

| Method and path | Permission |
| --- | --- |
| `GET /activity-seasons` | `activity-groups-manage`, module `groups` |
| `POST /activity-seasons` (`from`, `to`, `starts_on`, `ends_on`) | the same, throttled 20 a minute |

The navigation item is "Start a new season", next to Activity groups.
