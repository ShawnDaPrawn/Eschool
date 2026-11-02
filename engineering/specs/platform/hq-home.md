# HQ home page

Action Plan section 4F, "HQ home page". Owner role: Backend and Admin UX (HQ).

## User stories

- As Navigate Ed staff, the first page I see after signing in to HQ tells me where things stand across every school, so I know what needs me today without opening each area.
- As a member of a role, I only see the areas my role can open, and each one links to its own page.
- As a school, my learners', parents', staff's and contacts' details never appear on it: it shows counts and school names only.

## Where it is

- `GET /saas/home` (`saas.home`, `PlatformHomeController`), ability `schools.view`, which every role holds.
- Signing in to HQ now lands here (`LoginController`), instead of on the school list.
- The school list keeps its address, `/saas` (`saas.index`), so existing links and bookmarks still work. It is now also the "Schools" link in the HQ bar, and the platform name in the bar leads to the home page.

## Rules

- **Each tile reads an existing service.** `PlatformHome` puts the tiles together; it adds no table and stores nothing.
- **Only the tiles a person may open.** Each tile needs the same ability as its area's page (`PlatformHome::TILES`, `config/platform_roles.php`). A tile the role does not hold is not built, so it costs no query and its numbers never reach the page.
- **Missing feature, hidden tile.** A tile is offered only for a feature that is built. Billing, support access, data requests and school health flags are not built yet, so their tiles are not on the page at all (see "Not in this step").
- **Counts and school-level facts only.** No learner, guardian, staff member, HQ contact or platform staff name, address or free text (such as an incident's title) is shown. Schools are named, as on the school list.
- **Across all schools.** Each count includes every school; school-owned tables are read through the area's own service (`CurrentSchool::run` or `acrossSchools`).

## Tiles

| Tile | Ability | Reads | Shows | Links to |
| --- | --- | --- | --- | --- |
| Schools by stage | `schools.view` | `PlatformSchools::overview()` | number of schools per stage (Lead to Churned; a school without an HQ record counts as Onboarding), and how many are suspended or archived | the school list |
| Schools at risk | `schools.view` | the same | schools whose HQ stage is At risk | each school's page; the school list |
| Onboarding blockers | `schools.view` | `PlatformOnboarding::checklist()` | active schools that are Contracted or Onboarding; for the first 10, steps done of the total and the first step not done | each school's checklist; the school list |
| Open incidents | `incidents.manage` | `PlatformIncidents::list()` | incidents not closed: open, contained, high or critical, needing a school or Regulator notification, and how long the oldest has been open | the incident register |
| LURITS claims | `identity.review` | `PlatformLuritsConflicts::list()` | open claims (200 or more shows "200+") | LURITS claims |
| AI | `ai.view` | `PlatformAiControls::summary()` (new, from the same rows as the control centre) | safeguarding flags waiting (open and escalated), schools at or over today's cap, schools with AI paused, whether AI is on for the platform | the AI control centre |
| Platform health | `health.view` (new: Owner, Operations, Support) | `QueueHealth` (`oldestWaitSeconds`, new `backlog`, new `failedJobs`) and `DeliveryLog::failuresSince` (new) | jobs waiting on the database queue and the oldest wait, failed jobs in the last 24 hours and kept in total, failed, bounced and complained email and push messages in the last 24 hours | none: HQ has no health page yet |

When the queue is not the database queue (for example `sync`), the backlog and wait show "Not measured" and the tile says so. When the oldest due job has waited longer than `queue.worker_health.max_wait_seconds` (the same limit as `/up/worker`), the tile says the worker is not keeping up.

### Roles and what they see

| Role | Tiles |
| --- | --- |
| Owner | all seven |
| Operations | all seven |
| Support | all seven |
| Finance | Schools by stage, Schools at risk, Onboarding blockers |
| Content | Schools by stage, Schools at risk, Onboarding blockers |
| Sales (read-only) | Schools by stage, Schools at risk, Onboarding blockers |

## Speed

The page takes a fixed number of queries whatever the number of schools past onboarding: every tile is one or a few aggregate queries over all schools. Only Onboarding blockers reads per school (about 20 queries each, inside the school), and only for the first `PlatformHome::ONBOARDING_LIMIT` (10) schools being set up; the rest are counted. The HQ bar now reads the role's abilities once (`PlatformStaffAccess::abilities()`) instead of once per link, which saves about 30 queries on every HQ page. Measured with two schools: Owner 62 queries, Sales 42; the budget is 70 and 50.

## Acceptance tests (`Platform/HqHomeTest`)

| Test | Given | When | Then |
| --- | --- | --- | --- |
| `test_sign_in_lands_on_the_home_page_and_the_old_links_still_work` | a Sales member | signs in | lands on `/saas/home`; `/saas` is still the school list, and the bar's platform name links home |
| `test_the_owner_sees_counts_from_both_schools_and_never_a_person` | School A onboarding, School B at risk with a named HQ contact; incidents, LURITS claims, safeguarding flags, delivery failures, failed and waiting jobs in both schools | the Owner opens the page | every tile, with counts summed over both schools (older deliveries, jobs not yet due and old failed jobs left out); no learner, parent, staff, contact name, email or incident title, in the page or in the tile data |
| `test_each_role_sees_only_its_tiles_and_every_link_opens` | every role in `config/platform_roles.php` | each opens the page | exactly its tiles, in order; every link on a tile opens (200) for that role |
| `test_sales_sees_only_what_it_may` | Sales | opens the page | the three school tiles only; incidents, LURITS, AI, health and their links absent; those areas refuse Sales (403); the service itself returns only those tiles |
| `test_a_school_account_and_a_retired_role_are_refused` | a school Super Admin, a School A teacher, a School B teacher, a grant whose role left the config, a signed-out visitor | open the page | 403, 403, 403, 403 (no abilities), redirect to sign-in |
| `test_an_empty_platform_shows_quiet_tiles` | both schools live, nothing open, the `sync` queue | Operations opens the page | "No school is marked at risk", "No active school is being set up", zero incidents, backlog not measured |
| `test_the_page_takes_a_fixed_budget_of_queries` | the seeded schools | Owner and Sales open the page; a third, live school is added | the same number of queries with the third school; at most 70 (Owner) and 50 (Sales) |

`SaasPanelTest` and `PlatformRolesTest` now expect sign-in to land on `/saas/home`, and 40 `/saas` routes.

## Not in this step

- **Revenue this month and overdue** (SA.8 billing): not in main yet. Add the tile, with `finance`'s ability, when invoices merge.
- **Open support access sessions** (SA.9): not in main yet.
- **Open data requests** (SA.11 POPIA requests): not in main yet. The LURITS claims tile covers the part of data operations that is built.
- **Schools at risk from usage** (SA.10 at-risk flags): not in main yet. The tile shows the At risk stage set on the HQ school record; add SA.10's flags to it when they merge.
- **A platform health page** and errors beyond failed jobs (application error logs): not built; the health tile has no link.
- Charts or trends over time, and caching the page.
