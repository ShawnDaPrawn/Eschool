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

## Switch day (Action Plan 1.21, first part)

- **Welcome.** The wizard's carry step has a choice, ticked by default: "Welcome families to *year* in the app when it becomes current". After the switch, in the same transaction, one `school.new_year` notice goes to the learners enrolled in the new year on its first day (or today, if later) and their current guardians (`GuardianService::guardianUserIds`). It says "Welcome to *year*", gives the first day and links to the timetable (`/academics/timetable`). It names no learner or homeroom. Leavers and their families get nothing. The result reports how many people it went to.
- **Year-end check: every class has a grade.** It counts the classes of this year's homerooms that have no grade, because promoting by grade (1.18) needs them, and links to the classes page.
- **The leaver state** (read-only access until 31 March, replacing deactivation at the switch) and the alumni invitation hook follow in "Leaver state" below.

### Acceptance tests (`Academics/NewYearWelcomeTest`)

| Given | When | Then |
| --- | --- | --- |
| Asha placed in 2027, Ben (leaving) not | switch with the welcome | one notice, "Welcome to 2027", with 13 Jan 2027 and the timetable link, to Asha, Farid and Mona only; 3 reported |
| The same | switch without it | no notice |
| Grade 5 without a grade | year-end checks; set the grade; the wizard page; make current with `welcome` | "1 class has no grade" then done; the choice is on the page; 3 welcomed |

## Leaver state (Action Plan 1.21, second part)

Until now a learner marked as leaving was deactivated the moment the new year became current, so a Grade 12 or Grade 7 leaver lost their final reports on switch day, and the parent app refused their family. Now they become a **leaver**: they and their guardians can read the school's records about them until 31 March, and change nothing.

### User stories

- As a matric leaver, I can still read my final report, marks and documents in January and February, so I can apply for university and bursaries.
- As the parent of a leaver, I can still read my child's final reports and documents until the end of March, but I cannot change anything for them.
- As the school office, I see who the leavers are, apart from current learners, and they are not in any class list, register, group or carry of the new year.
- As the school office, I can bring a leaver back as a returning learner (for example a learner who failed matric and returns to repeat), or end a leaver's access sooner.

### Rules

- **When.** A learner whose row in the new year says "leaving" (`student_sessions.status` 0: the Promote learners page or Promote by grade) becomes a leaver when that year becomes current (`SessionYearService::applyPromotions`), or at once when they are marked as leaving the year that is already current (both promotion pages). Their account is no longer deactivated (`users.status` stays 1).
- **Data.** Their `Student` membership at the school (`school_memberships`) gets status `leaver`, `leaver_until` and `ends_on` set to the last day. The row stays as history.
- **The last day.** By default 31 March: the first 31 March on or after the later of the switch day and the new year's first day (`LeaverState::defaultUntil`). A switch in December 2026 or January 2027 into a year starting in January 2027 gives 31 March 2027. The wizard's preview names the date.
- **Guardians.** A guardian whose children at the school are all leavers gets a leaver `Parent` membership too, until the last of their children's dates. A guardian with a child still at the school keeps their normal membership (and can write for that child, not for the leaver). A staff member who is also a leaver's guardian keeps their staff access.
- **Read-only, through the existing gates.**
  - *Entering the school.* `MembershipService::active()` counts a leaver membership until the end of `leaver_until`, so `school.context` (`SetSchoolContext`), `/me/context` and the school switcher let the leaver and their guardians in. The v2 API's shape does not change.
  - *Writes.* Once the school is chosen, `SetSchoolContext` asks `LeaverAccess`: a person whose every membership at the school is a leaver one gets 403 for every POST, PUT, PATCH and DELETE (panel, v1 and v2), except the few requests that only read but are sent as POST (`config('leavers.read_only.allow')`). A learner or guardian whose roles are only family ones gets 403 for a write that names a leaver (the route's `learner` or `person`, or `learner_id`, `child_id` or `student_id`). Signing out and changing one's own password run without a school and stay open. `ProfilePolicy` also marks a leaver's profile read-only to the leaver and their guardians.
  - *Reads.* `ProfilePolicy::roles()` counts reachable roles (`SchoolMembership::reachable()`: active, or a leaver until `leaver_until`), so the leaver reads their own reports, marks and profile documents and a guardian reads their child's (`ReleasedMarks::learner`, `ReportPolicy::family`, profile and profile-file reads). `LearnerAcademicAccess::allows()` lets a leaver read during the period even without an enrolment that day (a declared mid-year departure still wins). The parent app's v1 guard (`parent.children.session.active`) treats a leaver child like a retained departure, and its profile answer (`get-profile-data`) shows a leaver child with their last homeroom, since their row this year has none (same fields as before).
- **Expiry.** At the end of `leaver_until` (school calendar) nothing has to run: the membership no longer counts, so the leaver and a leaver-only guardian are refused by `school.context`; and `GuardianService` (`guardianUserIds`, `childrenFor`) no longer gives anyone guardian rights to that learner, so a guardian who still has another child at the school gets 404 for the leaver's records and no more notices about them.
- **Not a current member.** `SchoolMembership::active()` leaves leavers out, so current-learner queries (messages, groups and their suggestions, registers, family reach, the welcome notice) and the new-year carries do too; houses already left out learners marked as leaving. Registers and class lists read the new year's enrolments and `student_sessions`, where a leaver has no homeroom.
- **Staff see leavers.** "Leavers" (Calendar and years, permission `promote-student-list`) lists them, newest first, with "Leaver: read-only until 31 Mar 2027" or "Access ended". The student list marks them the same way instead of a homeroom, and stops listing them once their access has ended (as when they were deactivated). The dashboard shows "Leavers (read-only)" as its own count while there are any; "Current-year learners" never included them.
- **Bring back.** The office (`promote-student-edit`) chooses a homeroom: the learner is enrolled in it in the current year from today (or the year's first day), their row there says "continues", and their membership and their guardians' leaver memberships are active again (`people.leaver_restored` in the audit log). Marking them "continue" on the Promote learners page does the same, with the homeroom chosen there.
- **End sooner.** The office (`promote-student-edit`) ends a leaver's access today: their membership ends, and so does each guardian's leaver membership unless another of their children is still a leaver (`people.leaver_access_ended`). Ending it twice is refused.
- **Isolation.** Every lookup is in the current school: another school's learner or homeroom is not found (404) and nothing changes in either school.
- **Alumni hook (Phase 4).** For each learner who becomes a leaver, the switch calls `AlumniInvitations::leaverStarted($learner, $membership)` inside its transaction. No alumni consent flag exists yet, so the bound implementation, `NoAlumniInvitations`, does nothing. Phase 4 binds its own (in `AppServiceProvider`) to record a consent the leaver gave and invite them after the switch commits; it must not contact anyone inside the transaction or change the leaver's access.

### Data

| Table | Column | Notes |
| --- | --- | --- |
| `school_memberships` | `status` | adds `leaver` to active, suspended and ended |
| `school_memberships` | `leaver_until` (date, nullable) | the last day of read-only access; kept as history when access ends or the learner returns is cleared |

Migration `2026_11_01_100000_add_leaver_until_to_school_memberships` (index on school, status and `leaver_until`). Rolling back is refused while any membership has a `leaver_until`.

### Endpoints (panel)

| Method and path | Permission | Notes |
| --- | --- | --- |
| `GET /learners/leavers` | `promote-student-list` | `leavers.index` |
| `POST /learners/leavers/{learner}/restore` | `promote-student-edit` | `homeroom_id`; `RestoreLeaverRequest`, `LeaverState::restore` |
| `POST /learners/leavers/{learner}/end` | `promote-student-edit` | `confirm`; `EndLeaverAccessRequest`, `LeaverState::endAccess` |

No API change.

### Acceptance tests (`Academics/LeaverStateTest`, `PromotionTest`)

| Given | When | Then | Test |
| --- | --- | --- | --- |
| Asha and Ben leaving 2026, Cara continuing; Farid the father of Asha and Cara, Mona Asha's mother, Gina Ben's guardian | the switch into 2027 (20 Dec 2026) | Asha and Ben are leavers until 31 Mar 2027 and still active accounts; Mona and Gina are leaver guardians; Farid is unchanged; the alumni hook heard of Asha and Ben once; only Cara is a current learner; a second switch changes nothing; School B untouched | `test_the_switch_makes_leavers_read_only_until_31_march_instead_of_deactivating_them` |
| The same | Gina reads Ben's marks, reports and profile; writes his profile or her own preferences; Farid writes Asha's and Cara's profiles; Ben reads and writes his own; Mona opens the parent app; Farid asks for leave for Asha | reads 200; Gina's writes 403; Farid refused for Asha (nothing written), allowed for Cara; Ben reads, his write 403; Mona 200; Farid's leave request 403, no leave written | `test_read_only_a_guardian_of_a_leaver_reads_but_is_refused_a_write` |
| The same | 31 Mar 2027 at 23:00, then 1 Apr 2027 | still readable on the last day; then Ben, Gina and Mona are refused (403), Farid gets 404 for Asha's marks and profile and still reads Cara's; Asha has no guardians | `test_expiry_after_leaver_until_the_leaver_and_guardians_lose_access` |
| The same | the office opens Leavers, the student list and the dashboard, then brings Ben back into a homeroom | Ben listed and marked "Leaver: read-only until 31 Mar 2027", the leaver count shows; Ben enrolled in 2027 in that homeroom, his row continues, he and Gina are active and Gina may write again | `test_the_office_sees_leavers_and_brings_one_back_as_a_returning_learner` |
| The same | the office ends Asha's access | Asha's and Mona's memberships end today; Farid and Ben unchanged; Asha refused, Farid 404 for her; ending again refused | `test_the_office_ends_leaver_access_early` |
| A teacher | opens Leavers, restores or ends Ben, or calls the service | 403; no membership or enrolment changes | `test_wrong_role_a_teacher_cannot_end_leaver_access_or_restore_a_leaver` |
| School B's Ben a leaver at School B | School A's office restores or ends him, or restores School A's Ben into a School B homeroom | 404; School B's memberships and every enrolment unchanged; School A's Ben still a leaver; the list shows nothing of School B | `test_wrong_school_school_bs_learner_cannot_be_restored_or_ended_from_school_a` |
| The wizard before the switch; a switch in May 2027 | open the wizard; work out the last day | "until 31 Mar 2027"; 31 Mar 2028 | `test_the_wizard_preview_names_the_last_day_and_a_late_switch_moves_it_to_next_march` |
| The Promote learners page | Ben leaves next year, it becomes current; he continues; he leaves the current year | a leaver until 31 March, account active; active again; a leaver at once | `PromotionTest::test_a_promotion_into_next_year_takes_effect_when_that_year_becomes_current` |

### Not in this step

- **The apps.** They do not know a person is read-only: the v2 API shape is unchanged, so `/me/context` carries no read-only flag and a refused write shows the usual "no access" error. A "leaver mode" in the learner and parent apps (and a read-only marker in `/me/context`, an additive v2 change with the Dart client regenerated) is a later step.
- **Signing in afresh as a leaver.** The v1 learner sign-in, and the v1 learner API (`student.session.active`), still refuse a learner whose current-year row says "leaving", as before. A leaver who was signed in keeps reading through v2 with their token (tokens last 30 days); a new sign-in for leavers waits for the apps' leaver mode. Guardians sign in as before.
- **Writes that name no learner.** A guardian who still has another child at the school is refused writes that name the leaver; a write that identifies the leaver some other way (a wallet or fee account id) is checked only by its own feature.
- **Learner passport export** (Suggestion S7) and an invitation to export it before access ends.
- **The alumni network** (Phase 4, Idea 10): only the hook exists.
- **Tidying ended leavers.** Nothing runs on 1 April; the dates decide. A later retention job may end lapsed leaver memberships (status `ended`) for reporting.
- Legacy pages that list learners by `students.class_section_id` without a year still list a leaver under their last homeroom, as they did when leavers were deactivated.
