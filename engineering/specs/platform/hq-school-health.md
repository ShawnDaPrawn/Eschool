# HQ school health and usage

Action Plan SA.10, the Build 1 part (core metrics). Owner role: Backend and Admin UX (HQ).

HQ needs to see early when a school is drifting: families stop opening the app, email starts bouncing, AI spend runs into the cap. This page shows each school's health from its own live data, flags the schools at risk, and compares them, with counts and shares only. It follows the onboarding checklist ([HQ onboarding](hq-onboarding.md)): no learner, guardian or staff record is listed, named or opened.

## User stories

- As Navigate Ed operations or support, I open a school's health page and see how many families used NavigateEd this week, how many learners we can reach through a guardian, who signs in by role, which modules are used, delivery problems, AI spend against the cap and failed jobs, as of now.
- As an Owner or Finance, I compare every school on one page, flagged schools first, to decide whom to call.
- As a school, my people never appear in HQ: the health pages hold counts only, and sensitive modules show no activity at all.

## Who

- New ability `health.view`: Owner (all abilities), Operations, Support and Finance. Sales and Content do not get it: no "School health" link in the platform bar or on the school page, and 403 on both pages.
- School accounts (a school's Super Admin, a teacher) never reach `/saas` (403).
- The service checks the ability itself (`PlatformSchoolHealth::authorise`), whatever the route.

## The figures (per school)

All windows end now. "The school's date" is in its timezone.

| Figure | Rule |
| --- | --- |
| Weekly active families | Guardians who signed in to or used an app in the last 7 days, out of the school's guardians. A guardian is someone `GuardianService::guardianUserIds()` counts for a current learner (active links, and legacy parents with no link row) whose account is on and who belongs to the school, as in Family reach (2.6). Activity is a sign-in token created or used (`personal_access_tokens.created_at`, `last_used_at`) or a push device registered (`user_devices.last_seen_at`). |
| Guardian reach | Current learners with at least one such guardian reachable by a verified email address or an active push device (`PushDevices`' rule: not revoked, and its sign-in still exists), also split by email and by push. |
| Sign-ins by role | People of each role group (parents and guardians, learners, teachers, other staff) with an active membership, and how many signed in to or used an app in the last 7 and 30 days. Someone with two roles counts in both. Sign-ins to the staff web panel are not recorded anywhere yet (file sessions), so office staff who use only the panel show as not signed in; the page says so. |
| Learners active in the app | Distinct learners with a `learner_activity_days` row in the last 7 days. |
| Module usage | The modules on (as the school sees them, `ModuleGate`), each with the messages it sent (distinct `delivery_key` in `notification_deliveries`, mapped to modules by `config/notification_types.php`) and the AI requests it made (`ai_usage` by feature) in the last 30 days. **Sensitive modules show no activity** (wellbeing, discipline, support plans, health forms and consents, early warning): even a count could tell HQ that something happened to a child. |
| Email and push | In the last 30 days, per attempt: emails sent (sent, delivered, bounced or complained), bounced, marked as spam (complaints from the SES webhook), could not be sent; push attempts and push failures. |
| AI this month | Spent and reserved cents (`ai_usage.cost_cents + reserved_cost_cents`, the school's month) against the cap for the month: the school's own daily cap (`ai_school_limits.limits.school_daily_cents`), never above the largest feature's `school_daily_cents` in `config/ai.php`, times the days in the month. Shows whether the school paused AI and whether AI is on for the platform. Amounts are in `ai.billing_currency`. |
| Failed jobs | Queued jobs of the school that failed in the last 30 days. A job carries its school in its Laravel context (`CurrentSchool` puts `school_id` there), so `failed_jobs.payload` names it. Counted by kind of job (class name only); the exception text is never read, as it can hold personal information. Jobs queued with no school are not counted for any school. |
| Not yet available | Storage (most uploads record no size), the school's own sending status (SES tenant, Action Plan 2.3, Build 2) and application errors (only in the logs). |

## At-risk flags

Rules and thresholds are in `config/school_health.php`; each rule is *at risk*, *OK* or *not enough data*. A rule whose figure is bad when high flags when the figure **reaches** its threshold; reach is the one minimum and flags **below** it. Rates use whole-number arithmetic, so 1 bounce in 20 emails is exactly 5%.

| Rule | Flags when | Needs |
| --- | --- | --- |
| Activity down (`activity_drop`) | weekly active families are 30% or more below the average of the nightly figures of the four weeks before | at least 4 nightly figures dated 7 to 28 days back, averaging at least 5 families |
| Low reach (`low_reach`) | under 60% of learners reached | at least 10 learners |
| Bounces (`bounce_rate`) | bounced emails 5% or more of emails sent in 30 days | at least 20 sent |
| Complaints (`complaint_rate`) | complaints 0.1% or more of emails sent | at least 100 sent |
| Push failures (`push_failure_rate`) | failed push attempts 10% or more | at least 20 attempts |
| AI spend (`ai_spend`) | 90% or more of this month's cap used | a cap |
| Failed jobs (`failed_jobs`) | 10 or more of the school's jobs failed in 30 days | none |

The flags do not change the school's stage on its HQ record (SA.4); staff decide that.

## Data and performance

`school_health_snapshots` (migration `2026_11_01_120000`): one row per school and **school's date**, with the headline counts as columns (guardians, active guardians, learners, reached learners, flag count), the full figures and flags as JSON, and when they were computed. **Required school-owned**: `school_id` not null, a foreign key, first in the unique `(school_id, snapshot_date)` index; the model uses `BelongsToSchool`, and HQ reads it across schools explicitly. The rows describe one school each and must go with the school. `down()` refuses to drop the table while it holds rows.

The nightly command `platform:school-health` (scheduled at 04:20, `--school=` for one school) computes every school that is not archived, replaces that day's row if run again, deletes rows older than `school_health.keep_days` (400), logs a failing school by id and carries on.

**Why both live figures and a nightly table.** One school's page is computed when it is opened: about a dozen grouped queries, each limited to that school's rows (`school_id` indexes, memberships, the school's learners' links), which stays quick for a school of thousands. Comparing every school that way would multiply that by the number of schools on each page view, so the comparison reads each school's latest nightly row and shows when it was computed. The nightly rows are also needed for the trend: a sign-in token keeps only its latest use and is deleted on sign-out, so last month's weekly active families cannot be worked out later. The school page reads its trend from the same rows. Threshold changes in the config apply to the comparison from the next night.

## Endpoints (HQ panel)

| Method and path | Name | Ability |
| --- | --- | --- |
| `GET /saas/health?sort=` | `saas.health` | `health.view` |
| `GET /saas/schools/{school}/health` | `saas.schools.health` | `health.view` |

Read-only: no form, nothing written by either page. `sort` is `flags` (default: most flags first, then name), `name`, `active` (lowest share of active families first), `reach` (lowest reach first) or `bounce` (highest bounce rate first); anything else means `flags`. Schools without nightly figures come last, marked "not computed yet". An unknown school is 404 for staff who may see health, and 403 for anyone else. No API route and no `openapi.yaml` change.

## Acceptance tests (`Platform/HqSchoolHealthTest`)

| Test | Given | Then |
| --- | --- | --- |
| `test_the_figures_come_from_the_schools_own_data` | School A: one guardian signed in this week with a verified email, one used the app 10 days ago with a phone, one unreachable; a teacher and a learner signed in; 19 delivered, 1 bounced, 1 complaint, 1 unsent email and an older bounce; 6 pushes, 1 failed, one a safeguarding alert; AI 150 + 50 reserved this month and 9000 last month under a 500 a day cap; a failed job of School A and one with no school | 1 of 3 guardians active; 2 of 3 learners reached (2 by email, 1 by push); parents 1 in 7 days and 2 in 30; learners and teachers 1 each; emails 21 sent, 1 bounced, 1 complaint, 1 unsent; push 6 and 1; communication 22 messages, chat 5, teaching assistant 2 AI requests, wellbeing activity not counted; AI 200 of 500 × days in the month; 1 failed job (SendMail); storage not available |
| `test_school_b_activity_never_counts_for_school_a` | then School B: every person signed in with a phone and a verified email, 30 bounces, 30 push failures, large AI spend, two failed jobs | School A's figures are identical; School B's carry its own activity |
| `test_flags_trigger_at_and_above_their_thresholds` | figures at, just below and above each threshold, and below each minimum sample | each rule at risk at and above its threshold (reach: below 60%, 60% is OK), OK below, not enough data below the minimum; a changed config threshold applies |
| `test_the_trend_reads_only_the_schools_nightly_figures_of_the_four_weeks_before` | nightly rows for School A 6, 7, 14, 21, 28 and 29 days back, and School B's | School A's trend uses the 7 to 28 day rows only; School B's are its own |
| `test_the_nightly_run_keeps_one_row_per_school_and_day_and_flagged_schools_come_first` | both schools seeded and a 500-day-old row; the command run twice | one row per school; the old row deleted; School A no flags, School B bounces, push failures and AI spend; the comparison lists School B first, and by reach School A first; an array `sort` is ignored |
| `test_a_school_without_nightly_figures_is_listed_as_not_computed` | no nightly rows | "not computed yet" on the comparison; the school page says no nightly figures and the trend has not enough data |
| `test_each_allowed_role_sees_the_pages_and_others_are_refused` | Owner, Operations, Support, Finance; Sales, Content; a school Super Admin and a teacher | the first four see the link and both pages (unknown school 404); Sales and Content get no link and 403 (also for an unknown school); school accounts 403; the service refuses Sales itself; nothing written |
| `test_the_pages_show_counts_and_never_a_person` | both schools seeded and computed | no learner, guardian or teacher name, no email address, no learner number, no failed job's exception text on School A's page, the comparison or School B's page; School A's page shows no School B text |

`SaasPanelTest` counts 38 `/saas` routes, each naming its ability.

## Security notes

- Every figure is an aggregate, and every query names the school (`school_id` predicates, the school's memberships and learners). Emails are only tested in SQL for being verified; no address, name or number is selected for display.
- A person at two schools counts at both (their app sign-ins are not per school), as in Family reach.
- Sensitive modules show no activity.

## Not in this step

- Delivery views of Build 2: the school's own SES tenant status (2.3), per-type delivery trends and bounce lists.
- Analytics events (0.9, not built) as a better activity signal than sign-in tokens; recording staff web-panel sign-ins.
- Storage per school (needs file sizes recorded for every upload) and application errors per school (needs error tracking with the school in its context).
- Alerts when a school becomes at risk, a HQ home page tile, and setting the school's stage to "At risk" from the flags.
- A per-school history chart beyond the four-week average.
