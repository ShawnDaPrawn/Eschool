# Fixtures and leagues between schools

Action Plan step 5.2. Owner roles: Backend, Admin UX and Test. The first use of [records shared between schools](../platform/shared-records.md) (5.1). Built on [activity groups](groups.md): each school's team is one of its own activity groups. Module `groups`.

## User stories

- **Activity manager (host):** I arrange a fixture for one of my groups against another school on NavigateEd, or against a school or club that is not on NavigateEd (typed by name). I set the date, time, venue, field and whether we play at home.
- **Activity manager (guest):** I see the invitation with the host's name, date and venue, and accept it for one of my groups with the team name the host will see, or decline it.
- **Activity manager or the group's coach:** I keep my side: the team name the other school sees, the team sheet (learners from the group on the fixture day, each with a label and position), whether to share the sheet, and private notes. I report the score from the fixture day.
- **Both schools:** the result counts once both schools report the same score. If they differ, each sees the other's report so they can sort it out. Against a team outside NavigateEd, the host's report is the result.
- **League organiser and members:** I start a competition (name, season, points for a win, draw and loss), invite schools, and every accepted member sees the running table from confirmed results.
- **Guardian of a learner on the team sheet:** I get a private notice that a result is ready, and see my child's team's fixtures and confirmed scores. A guardian whose child is not on the sheet sees nothing.

## Rules

- **Who may do what.** Managers (`activity-groups-manage`) arrange, reschedule (host only), answer and start or join competitions. Staff assigned to the side's group (`CommunityAuthority::groupStaff`) keep that side and report its score but cannot change its group. Parents and learners never reach staff reads (403). A teacher without the group gets "not found".
- **What each school sees** (`Fixtures::view`): the shared facts, both schools' names and states, its own side in full, and, once both accepted, the other's team name, the other's sheet labels and positions if shared, and the result. Before both accept, no team name, sheet, score or side of the other school is shown, and no score can be reported.
- **Team sheet.** Only learners actively in the side's group on the fixture day. A label defaults to a first name and initial ("Asha D.") and can be changed before sharing. Saving replaces the sheet. Learner ids and notes never leave the school.
- **Scores.** From the fixture day, by an accepted school only. Each school's report is on its own side; the shared row holds only an agreed result (`result_state`: `none`, `awaiting`, `disputed`, `confirmed`). Reporting the same score again, or replaying an operation, changes nothing and sends nothing. A school that changes its report after confirmation reopens the result (disputed) until both agree again; `result_revision` counts each confirmation.
- **Families.** On confirmation each school notifies, inside its own school, the guardians who may read a learner on its sheet today (`CommunityAuthority::learnerIds`). The notice (`fixtures.result_confirmed`, private scope `FixtureNotices`, notice-only) says only that a result is ready; the inbox and delivery recheck the sheet, the guardian's link and the result revision, so a learner taken off the sheet or a reopened result withdraws it. The family read lists fixtures where both schools accepted, with the team name, the opponent's name, home or away and the confirmed score. No other learner, ranking or table is shown to families.
- **Competitions.** A fixture in a competition needs both NavigateEd schools to be accepted members; an outside opponent may play in it. Standings count every confirmed, not cancelled fixture of the competition (at most 500): played, won, drawn, lost, for, against, difference and points, sorted by points, difference, goals for and name. A team is the school's name and its team name for that fixture, or the outside opponent's name. Only accepted members see members and standings; an invited school sees the competition's name and organiser.
- **Competition type seam.** Fixtures and competitions carry a `kind`. `config/shared_records.php` `competition_kinds` lists the kinds a competition may be; only `sport` exists in this step. Academic competitions and debating (5.3) add their kind and their own scoring there.

## Acceptance tests (`Activities/InterSchoolFixturesTest`)

| Given | When | Then |
| --- | --- | --- |
| School A and Baobab | A arranges, Baobab accepts, both keep their sides, A reports 2–1, then Baobab 2–1 | awaiting then confirmed; Baobab's shared sheet seen by A; A's unshared sheet not seen; each school's own guardian notified in its own school; each family sees its own view of the score |
| A teacher without the group, a parent, the group's coach, the guest's manager | arrange, list, open, keep the side, report, reschedule, start a competition | 403 or not found; the coach keeps the side and reports but cannot reschedule or start a competition; the guest cannot reschedule |
| Marula College (no part) | every read and write of the fixture and the league | not found or empty, no rows and no notices |
| Both accepted | each opens the fixture | none of the other's learner names, ids, positions, notes or group; with sharing, labels and positions only |
| Baobab invited, then declined | opens the fixture; A reports | the host's name and the facts only; 409; no side created |
| Ben's guardian (Ben not on the sheet) | the family read and the inbox | nothing; Asha's guardian sees it until Asha is taken off the sheet |
| Replay, a new id with the same score, a conflicting score, a changed report, a future fixture | report | one receipt, no second notice, disputed with each other's report, reopened and the notice withdrawn, 422 |
| An outside opponent | the host reports | confirmed; the family sees away and 0–2 |
| A league with Baobab invited, then accepted | open it; fixtures in it | members and standings only after accepting; fixture against a non-member refused; table counts the league's confirmed results only |
| The API and the panel | the same flows | responses match openapi.yaml; panel pages for managers only |

## Endpoints

API v2, middleware `auth:sanctum`, `school.context`, `role.any`, `learner.approved`, `module:groups`, private no-store. Writes take `operation_id`.

| Method and path | Who |
| --- | --- |
| `GET /activities/fixtures` | staff (managers: all; staff: their groups' fixtures) |
| `POST /activities/fixtures` | manager |
| `GET /activities/fixture-schools?search=` | manager (at least 3 characters, 10 names) |
| `GET /activities/fixtures/{fixture}` | participating staff |
| `PUT /activities/fixtures/{fixture}` | host manager |
| `POST /activities/fixtures/{fixture}/decision` | invited manager |
| `PUT /activities/fixtures/{fixture}/side` | manager or the side's group staff |
| `POST /activities/fixtures/{fixture}/result` | manager or the side's group staff |
| `GET /activities/fixtures/learners/{learner}` | the learner's guardian (or the learner) |
| `GET`/`POST /activities/competitions`, `GET /activities/competitions/{competition}`, `POST .../invitations` (organiser), `POST .../decision` (invited) | staff; managers write |

Schemas: `Fixture`, `FixtureSummary`, `FixtureSide`, `LearnerFixture`, `Competition` and their writes in `openapi.yaml`.

## Screens (panel)

`/activity-fixtures` (navigation: "Fixtures between schools", next to Activity groups; `activity-groups-manage`): invitations waiting, the fixture list, "Arrange a fixture" (find a school by name, or type an outside opponent), competitions and "Start a competition". `/activity-fixtures/{fixture}`: the fixture, the answer form, the team sheet, the other school's shared sheet, the score and the host's date and venue form. `/activity-fixtures/competitions/{competition}`: the answer form, standings, members and inviting a school. Material 3 components and `page-table__table` tables.

## Notification types

`fixtures.result_confirmed` ("Match results", module `groups`, parent portal, notice-only, private scope `fixtures.` → `FixtureNotices`).

## Not in this step

App screens (parent and coach) for fixtures and results; withdrawing from an accepted fixture or league; moving a fixture to another opponent; more than two teams in one fixture (tournaments); HQ views; academic competitions and debating (5.3).
