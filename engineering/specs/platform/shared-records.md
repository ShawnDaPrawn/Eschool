# Records shared between schools

Action Plan step 5.1. Owner roles: Backend and Test. The pattern for every feature that only works when several schools are on NavigateEd. Its first use is [inter-school fixtures](../activities/inter-school-fixtures.md) (5.2). Read it with [tenancy](tenancy.md): everything there still holds.

Until now every record belonged to one school. A fixture between two schools belongs to both, and each must still see only its own learners, roster and notes. A shared record is a platform-scoped row that links two or more schools, where each school sees only its own side.

## User stories

- **Activity manager at the inviting school:** I invite another school to a fixture or a league by finding its name. Until it accepts, I see only its name and whether it has answered.
- **Activity manager at the invited school:** I see who invited us and the shared facts I need to decide (the date and venue of a fixture, the name of a league). Nothing else of theirs is shown until we accept, and nothing of ours reaches them.
- **Both schools after accepting:** each sees the other's name and what the other chose to share (a team name, a team sheet's labels), never its learners, groups, notes or anything else.
- **A third school:** a shared record it does not take part in does not exist for it. Typing its id gives "not found".
- **Engineer:** I add a shared record type by following the five rules below, and the boundary test tells me if I read another school's rows outside them.

## The five rules

1. **The shared row names no school.** A shared record is a global table `shared_<thing>` (no `school_id`, listed in `SchoolOwnedTables::GLOBAL` with its reason). It holds only facts that every accepted participant may see: for a fixture, the date, venue, field, an outside opponent's name and the result both schools confirmed. It holds no learner, group, note or school id.
2. **Each school links itself with its own row.** `shared_participants` is school-owned (required `school_id`, `BelongsToSchool`): one row per school per record, with `record_type`, `record_id`, `role` and `state` (`invited`, `accepted`, `declined`). A school finds its shared records only through its own rows, so the ordinary school scope keeps a third school out. The creating school's row is `accepted` from the start; an invited school's row is written inside the invited school, as `invited`. Answering is final.
3. **Private things stay in school-owned rows.** Each school's side (`shared_fixture_sides`, `shared_fixture_selections`) is school-owned like any other school data, with composite school foreign keys so a side can only point at its own school's group and learners.
4. **Another school's rows are read in one place, and only declared columns.** `App\Services\Platform\Shared\SharedRecords` is the only code that reads another school's rows of a shared record:
   - `participants()` reads every participant row of one record, and only its school, role and state (to show names);
   - `published()` reads the columns a feature declared in `config/shared_records.php` `published`, and refuses any other column or model. The feature calls it only once the schools concerned have accepted.
   Learner ids, groups, notes and the person who reported are never declared (`InterSchoolFixturesTest::test_shared_records_are_only_reached_through_the_shared_records_boundary` checks the list and that no other code names the shared models or tables).
5. **Work for another school runs inside that school.** `inSchool()` runs a callback in a participant's own school context (its scope, settings and roles), for example notifying that school's own families of a confirmed result, or asking a yes-or-no question of its own rows (does a new fixture date suit its team?). Only that answer comes back; its private rows are never returned to the asking school.

Accepting is the consent: until every school taking part has accepted, each school sees only the other's name (and the invited school sees the shared facts it needs to decide). Platform staff working inside a school (impersonation or a support session) cannot make, answer or read shared records (403): a support session never reads across schools.

## Writes

`SharedRecords::once()` gives every write an operation id: the school row is locked, authority is checked fresh, the same operation returns its stored (encrypted) receipt, and the same id with a different request is refused (409). Receipts are school-owned (`shared_record_operations`). A write that changes the shared row locks it (`lockForUpdate`) so two schools' writes are applied one after the other. Under REPEATABLE READ a transaction's snapshot is fixed by its first plain read (the authority checks), before it waits for that lock, so anything it reads from the other school after the lock and acts on is read with a locking read (`published(..., current: true)`, a shared lock), which sees the latest committed rows. Every write is audited in the acting school, without learner ids or note text.

## Acceptance tests (`Activities/InterSchoolFixturesTest`)

| Given | When | Then |
| --- | --- | --- |
| A fixture between School A and Baobab | Marula College (taking no part) opens, answers, edits, scores or lists it, or its league | not found, an empty list, no participant row and no notice at Marula |
| Baobab has not accepted, or declined | it opens the fixture | the host's name and the fixture's own facts only; no team name, sheet, score or side; no score can be reported |
| Both accepted, sheets not shared | each opens the fixture | the other's school and team name; never its learners, ids, positions, notes or group |
| A shares its sheet | Baobab opens it | the labels and positions A gave, never names or ids |
| Any school | asks `published()` for notes, a group, a learner id or another model | refused (`LogicException`) |
| The code base | searched | only the shared-record services, models and config name the shared tables |

## Data changes

`2026_11_08_150000_create_shared_records_and_fixtures`: `shared_record_operations` and `shared_participants` (school-owned), `shared_competitions` and `shared_fixtures` (global), `shared_fixture_sides` and `shared_fixture_selections` (school-owned). A side's group uses the existing `(school_id, id)` unique index on `activity_groups` for its composite foreign key. down() refuses while any of the tables holds rows, then drops them.

## Adding a shared record type

1. A global `shared_<thing>` table for the shared facts, sorted in `SchoolOwnedTables::GLOBAL` with an honest reason.
2. Its type in `config/shared_records.php` `types`.
3. School-owned tables for each school's private side, in `SchoolOwnedTables::REQUIRED`, with composite school foreign keys.
4. The columns the other schools may read, in `published`.
5. A service that calls `requireHere()` before anything else and `published()` only once `open()` is true (or the schools concerned accepted).
6. Tests: a third school sees nothing; a school that has not accepted sees nothing beyond the name; the other school never sees private rows.

## Not in this pattern yet

Withdrawing after accepting, more than two schools on one fixture (the participant table already allows it), and HQ views of shared records.
