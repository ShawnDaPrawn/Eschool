# Anonymous benchmarking (Action Plan 5.5)

A principal wants to know whether their school's attendance, learning gains
and family reach are good *for a school like theirs*. This step shows, on the
leadership dashboard, the school's own figures next to the median and
quartiles of comparable schools on NavigateEd, without ever naming or
identifying another school and without any learner data leaving a school.

## User stories

- As a principal or school administrator, I choose whether my school takes
  part. Once it does, I see each week my school's attendance, results movement
  and guardian reach next to the lower quartile, median and upper quartile of
  comparable schools, and whether my school is in the lower quarter, the
  middle half or the upper quarter.
- As a school that does not take part, my figures are never worked out or
  counted, and I see no comparison (reciprocity).
- As any school, I can never tell which schools are in my group, how many
  there are, or any one school's figure.
- As HQ (Owner, Operations, Support, Finance: `health.view`), I see the same
  published figures for every group, and nothing more.

## Who

- **Seeing and choosing (school):** the people who may open the leadership
  dashboard (`LeadershipAuthority::principal`): a current principal
  appointment, or a school Admin/Super Admin with academic management, in the
  school's own context (not across schools, not impersonating, not a platform
  identity, password set), with the `leadership_dashboard` module on. Anyone
  else is refused (403); a disabled module is 404. Authority is checked again
  after reading.
- **HQ:** `GET /saas/benchmarks` (`saas.benchmarks`, ability `health.view`),
  linked from School health. The service checks the ability itself.

## Comparable schools

A school's comparison group is `sector:stages:band`, worked out each week from
its own records:

| Part | Rule |
| --- | --- |
| Sector | `school_accounts.school_type` (public or independent), as HQ recorded it (SA.4). A school without one is not placed. |
| Stage mix | The age stages ([school stages](../platform/school-stages.md)) of the school's learners enrolled on the period end (dated `enrolments`, homeroom grade, the school's own stage mapping). A stage is in the mix when it holds at least `stage_share_bp` (10%) of the placed learners, so one stray learner does not change a school's group. |
| Learner band | Learners enrolled on the period end, in `learner_bands` (1–299, 300–599, 600–999, 1000–1499, 1500 or more). |

A school that cannot be placed sees its own figures and a note, never a
comparison.

## The figures (school-level, basis points)

| Figure | Rule | Minimum |
| --- | --- | --- |
| Attendance | Recorded attendance over the 28 days to the period end, counted exactly as the leadership dashboard counts it (`LeadershipDashboard::attendance`: physical school days, half days, holidays excluded, canonical over legacy, a matching dated enrolment). | 100 recorded learner-days |
| Results movement | In the school term covering the period end (or the latest that started), the share of learner-topics measured at both the start of the term and now (staff audience, `recent20-v1`, `mastery_growth_snapshots`) that rose a mastery level: the mastery impact report's "rose". Unavailable when the mastery module is off. | 20 measured learner-topics |
| Reach | `reached_learners / learners` from the school's own nightly health figures (`school_health_snapshots`) at most 7 days before the period end: learners with a guardian reachable by verified email or an active push device. | 10 learners |

A figure below its minimum is "not enough data at your school", never zero,
and the school is then not counted for that figure.

## Data

| Table | Owner | Holds |
| --- | --- | --- |
| `benchmark_participations` | required school-owned | One row per school: `opted_in`, who changed it and when, when it last left. |
| `benchmark_school_values` | required school-owned | Per school, period end and figure: the group key, the school's own value (basis points or null) and whether it was counted in that group's published figures (`contributed`). School-level numbers only. |
| `benchmark_releases` | **global** (in `SchoolOwnedTables::GLOBAL` with its reason) | Per period end, group and figure: `released` or `held`, the period whose schools produced the figures (`basis_period_end`), and the lower quartile, median and upper quartile. **No school column, no school's own value, no count.** |

Migration `2026_11_09_150000_create_benchmarking_tables`; `down()` refuses to
drop the tables while they hold rows.

## The weekly snapshot

`benchmarking:snapshot {--period=}` runs every Monday at 05:10 (UTC scheduler,
after the nightly health and mastery figures) for the week that ended on the
Sunday before (Africa/Johannesburg). It is the only writer.

1. The schools that take part (`opted_in`, school not archived) are listed.
   The period's rows are deleted first, so a second run gives the same result.
   A `--period` must be a past Sunday no earlier than the latest published
   week (history is never rewritten behind a later week).
2. Each school's group and figures are worked out **inside that school**
   (`CurrentSchool::run`, `BenchmarkMetrics`) and kept as its own rows. A
   school that fails is logged by id and skipped (and so is not counted).
3. For each group and figure, the schools with a value are the members.
   - **k-anonymity:** fewer than `min_group_size` (k, default 5, never below
     3) members: nothing is published, and the dashboard shows nothing for
     the figure, not an older week and not a smaller group.
   - **Differencing guard:** otherwise, compare the members with the members
     whose values produced the group's last published figures. If they differ
     by at least one but fewer than `min_membership_change` (m, default 3,
     never below 2) schools, counting joiners plus leavers, the last figures
     are copied unchanged (`held`, with their original `basis_period_end`).
     Otherwise new figures are published from the current members, and those
     members are marked `contributed`.
   - Quartiles are interpolated between the two nearest values ("type 7"),
     in whole numbers, then rounded half up to `round_bp` (whole percentage
     points).

### Why this guard

With exact medians and quartiles of a small group, a school that sees the
figures before and after one other school joins or leaves can often solve for
that school's value (for example, with five members the new median of six
lies halfway between two known order statistics). The guard removes that
"one change" signal:

- Figures only move when the membership is unchanged (every member's own
  values moved; no one school can be isolated) or when at least three schools
  changed together. Even if one of the three is the viewer itself, at least
  two unknown schools changed at once, so a shift reflects them jointly, and
  the viewer does not know which schools, or how many, were involved.
- The comparison is measured against the **last published** membership, not
  last week's, so changes cannot be fed in one at a time; a group that falls
  below k and comes back is compared against its last published membership
  too.
- A school that leaves may join again only after `rejoin_after_days` (90), so
  it cannot leave and rejoin to manufacture changes around one other school.
  Leaving is always allowed at once (it is the school's consent).
- Only medians and quartiles, rounded to whole percentage points, are ever
  published: never a count, a mean, a minimum or maximum, or a list. The
  school's own value is shown to the same precision.
- The figures are per group and figure; groups never overlap.

We chose a minimum-change rule over random noise: noise on small groups either
has to be large enough to make the figures useless or small enough to be
averaged away over repeated weeks, and noise would need its own stored state
to stay consistent. Held figures are shown with the week they come from.
Figures published while a school took part stay shown (held) after it leaves
until the group has changed enough; no new figure uses its values.

## What a school sees

`GET /leadership-dashboard` gains a "Comparable schools" section
(`SchoolBenchmarks::read`):

- Not taking part: an explanation, "Take part in benchmarking" (refused with
  the date while the rejoin wait runs), and no figures.
- Taking part: the latest week whose figures were worked out **after** it
  (last) joined (until then, "first comparison after the next weekly update");
  its group in words; per figure its own value, then the published lower
  quartile, median and upper quartile, and which quarter it falls in, or "Not
  enough comparable schools yet"; held figures say the week they come from;
  and "Stop taking part".
- `POST /leadership-dashboard/benchmarking` (`leadership-benchmarking.update`,
  `BenchmarkingRequest`, `participate` boolean, 10 a minute) records the
  choice; `SchoolBenchmarks::choose` checks authority again and locks the row.
  Audited as `benchmarking_joined` / `benchmarking_left`.

## Acceptance tests

`admin-panel/tests/Feature/Leadership/BenchmarkingTest.php`

| Test | Checks |
| --- | --- |
| `test_a_participating_school_sees_its_own_figures_next_to_the_median_and_quartiles_of_comparable_schools` | School A's own figures from its records (attendance 50%, reach 70%, group `public:junior:0`); joining by POST (audited); nothing until a weekly run after joining; quartiles of five schools; position band; a figure with four schools shows nothing; no school name on the page; weekly schedule entry; a second run gives the same figures; an older period is refused; HQ sees the same aggregates and no school. |
| `test_teachers_parents_and_other_hq_roles_are_refused_and_nothing_is_written` | Teacher 403, parent turned away, service refuses both; pending password and platform identity refused; invalid input; HQ Sales and Content refused; a school Super Admin cannot open `/saas/benchmarks`. |
| `test_a_school_that_does_not_take_part_sees_nothing_and_is_never_counted` | No rows worked out for a school that has not joined; it sees nothing; after leaving it sees nothing at once; rejoining waits 90 days; figures from before joining again are not shown. |
| `test_a_group_smaller_than_k_shows_nothing_rather_than_a_smaller_group` | Four schools publish nothing (school and HQ); k below 3 is not used; another group never fills this one; a group that shrinks below k shows nothing, not its older figures. |
| `test_no_other_school_can_be_identified_from_what_is_published` | The global table has no school column and only aggregates; figures are rounded to whole percentage points; no other school's exact value, count, id or name in the data, the page or HQ; School A reads only its own rows. |
| `test_the_differencing_guard_keeps_the_last_figures_until_enough_schools_have_changed` | One join: held; one leave as well (two changes since the published week): held; two more joins: new figures from every current school; same members with new values: published; m below 2 is not used. |
| `test_each_school_decides_and_reads_only_its_own_and_another_schools_context_is_refused` | School B's choice is its own; its figures are worked out in its own school; School A leaving changes only School A; School A's principal is refused in School B's context and across schools. |

## Not in this step

- App or `/api/v2` views of the comparison (server-side panel only; no API or
  OpenAPI change).
- More figures (fee collection, wellbeing, marks) and a history chart.
- School-chosen comparison groups (curriculum, province, quintile); groups are
  fixed by sector, stage mix and learner band.
- Retention of old weekly rows (school-level numbers only; a pruning rule can
  follow once a year of history exists).
- Letting the school set its own sector; it comes from HQ's school record.
