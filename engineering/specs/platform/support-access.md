# Audited support access

Action Plan SA.9. Owner role: Backend and Admin UX (HQ and school panel).

HQ's rule is "aggregates by default": Navigate Ed staff never see a child's personal information in `/saas`. This is the one way into a school's records. A Navigate Ed staff member asks the school, with a reason; the school's Super Admin approves or declines; an Owner can record an emergency override that opens access at once and tells the school immediately. Access is time-limited and read-only, never reaches clinical, counselling, health, wellbeing, safeguarding, discipline, early-warning or support-plan records, and every page viewed is logged for the school to see. Navigate Ed is the school's operator under POPIA; the school is the responsible party and decides.

The [SaaS panel spec](saas-panel.md) deferred this until it was decided what platform staff may see. This spec is that decision.

## User stories

- As Navigate Ed Support or Operations, I ask one school for access, saying why, for how long and what I need to do (only "read only" exists), so the school can decide.
- As a school's Super Admin, I am told at once, I approve or decline in my own panel, I see who is in my school right now and until when, I can end it early, and I can see every page they opened.
- As an Owner, when a school cannot be reached and waiting would cause harm, I open a waiting request at once with a recorded reason; the school is told immediately and sees the reason.
- As the staff member, I open the school in a support session that shows me what I need to diagnose set-up and sign-in problems, and I end it when I am done.
- As a learner, parent or teacher, nothing pastoral, medical or safeguarding about me is ever shown to Navigate Ed, and the school can see whenever my account page was opened.

## Rules

- **Who may ask.** The platform ability `support.access`: Owner, Operations and Support. Sales, Finance and Content are refused (403) on every HQ page and action and cannot open a session, even with a grant from before a role change. The emergency override is `support.emergency`, which only Owners hold (`config/platform_roles.php`). School accounts never reach `/saas` or a session.
- **The request.** One active school; a reason of 20 to 500 characters (the school reads it; refer to tickets and records by id, not by name); a length from `config/support_access.php` (30, 60, 120 or 240 minutes, never more than `max_minutes`, 240); and a scope. A person may have one open request or session per school (409 for another).
- **The school's answer stands (cool-down).** After the school declines a request or ends a session early, nobody may ask that school again, and no request to it may be overridden, for `refusal_cooldown_hours` (24): 409. This stops a decline being undone by filing a new request and overriding it.
- **Nobody to tell.** A request to a school with no active Super Admin (other than platform staff) is recorded but cannot be approved; HQ's list and the request's page say "Nobody at this school can approve it". An override of it is refused (409): access never opens with nobody at the school told.
- **Scope.** Only `read` exists. Every write is refused whatever the scope (below). A write scope would have to be named in the config **and** designed in its own step: each candidate considered (resend an invitation, reset a password, correct a class) changes a person's account or record, so none is offered now.
- **Telling the school.** `support_access.requested` goes to the school's active Super Admins, on every channel, mandatory and locked, linking to `/support-access`. It names no person, reason or record. A Super Admin account that is also platform staff is not told.
- **Deciding.** Only an active Super Admin of that school, in their own school, decides. A platform staff account that also holds a school role cannot approve (the same exclusion as restricted records), and the person who filed a request can never approve, decline or end it from the school side (403), even if they later stop being platform staff. Approval opens the session window from that moment for the length asked. A request nobody decides lapses after 24 hours (`pending_hours`) and can no longer be approved or overridden.
- **Emergency override.** An Owner opens a request that is still waiting (not declined, withdrawn or lapsed), with a reason of 20 to 500 characters, when the school cannot be reached. It is refused when:
  - the request has waited less than `override_after_minutes` (15): an override is for a school that cannot answer, not a way to skip its answer (409);
  - the Owner filed the request themselves: another Owner must decide (403);
  - the school declined or ended support access within the cool-down (409);
  - the school has no active Super Admin to tell (409).

  It opens at once for the length asked; `support_access.emergency` goes to the school's Super Admins immediately (same channels). The override form shows the school's latest decline or early end (at any time), and the override's audit entry records it (`earlier_refusal`: the grant id, its status and when). The school sees "Opened by a Navigate Ed emergency override, without your approval" and the emergency reason on its list and on the session's page.
- **Ending.** It expires by itself. The school's Super Admin can end it early (or decline a waiting request). The requester can withdraw a waiting request or end an open session; an Owner can do the same for anyone's.
- **Who opens it.** Only the requester, while the grant is open, and only while they still hold `support.access`. Another person's grant answers 404, like no grant.
- **Logging.** Each page that answers successfully is one `support_access_views` row (time, route name, the record's route parameter name and id, and for a list or search the ids of every account it showed, `listed`) and one `platform_support_page_viewed` audit row against the school with the same ids. Never content and never the search words. The school sees each list or search as "N accounts listed" with the account ids; HQ sees the same on the grant's page and in the HQ audit log. Refused attempts write nothing to the database; each is logged to the application log as `support_session_refused` with the grant id, the user id and a reason (`not_support_staff`, `not_found`, `not_open`, `scope`, `school_inactive`, `write`, `cross_site`).
- **Search words stay out of URLs.** The people search is a POST read (`support-session.people.search`), the only POST the session middleware lets through; it changes nothing, and its words never appear in a URL, the server's access log or browser history. The staff list (GET) ignores any query string.

### How the session's request context is set (and why)

A session is a separate, fixed set of read-only pages (`/support-session/{grant}/…`), not the school's own panel. The school panel has well over a thousand routes whose controllers check school roles and permissions themselves; letting a staff member through them would mean giving them school permissions (a role in all but name), and every controller would need auditing for writes on GET and for restricted data on shared pages (a learner's profile can hold medical fields). A fixed set of pages built for diagnosis can be reviewed completely; adding a page is a reviewed change.

The `support.session` middleware (`EnsureSupportSession`) runs after `auth`, on those routes only, before anything reads school rows:

1. It refuses anything but GET and HEAD (and the one POST read above) with 403, before the grant is looked up, and refuses any request a browser marks `Sec-Fetch-Site: cross-site`, so a link or form on another site cannot plant a page view (the session cookie is SameSite=lax).
2. It checks the grant on fresh rows: the signed-in person's own, open now (approved or overridden, started, not ended, before its expiry), read scope, an active school, and the person still an allowed platform staff member with `support.access` (`SupportAccess::session`).
3. It takes the school **from the grant alone**: never a header, the panel's school choice or a parameter. It sets `CurrentSchool::supportSession($school, $grant)`: the school with the impersonation flag. There is no membership and no role: Spatie's team becomes the school, where the staff member holds none, so every permission check fails. `school.context` and `platform.staff` are deliberately absent (one reads memberships; the other refuses impersonation); the same platform checks run in step 2 before the school is set.
4. For the rest of the request the session cannot leave its school: `set()` to another school, `allowAcrossSchools()` and `forget()` throw, and `run()` in the same school keeps the session. Only `reset()`, called by `ResetSchoolContext` at the start of the next request (and by tests), clears it. Jobs and the console never hold a session, so `forget()` in `Context::hydrated` is unaffected; a queued job carries only the school's id, never the session (the session pages queue nothing).

### Restricted records

The session pages query none of them. Independently, their own authorities refuse a support session explicitly (`abort_if(isSupportSession())`), in addition to refusing impersonation and platform staff identities as before: `SensitiveRecordsAccess` (counselling and the shared contract), `WellbeingAccess` (incidents, cases, counselling), `RiskAuthority` (early warning and interventions), `SupportAuthority` (support plans), `DisciplineAuthority`, `LearnerNotesPolicy` and `HealthForms` (health forms). Notices from those areas already refuse impersonation (`PrivateNoticeScopes`).

### What the pages show

- **Overview:** the school's name, status, package, time zone and current year; how many classes, sections, subjects and waiting staff invitations; people with an active role, counted by role; each module on, off or read-only.
- **People:** staff accounts (any role but Parent and Student), up to 200. A search (POST, at least three characters) finds up to 25 accounts at the school: staff whose name or email contains the words, and any account whose email is exactly the words. A learner or parent is never found by a fragment of a name or email. Each with roles here and whether the account is active.
- **One account:** name, email, whether the account is active, the email confirmed and a password change pending; each role here with its status and dates; its latest staff invitation's state. No profile field, record or message. Someone with no role at this school (another school's person, or a platform staff account) is 404.

## Data

| Table | Tenancy | Holds |
| --- | --- | --- |
| `support_access_grants` | required school-owned (FK to the school; `SupportAccessGrant` uses BelongsToSchool) | the request (requester, their role then, reason, scope, minutes), the decision or emergency override and its reason, the window (`starts_at`, `expires_at`) and how it ended |
| `support_access_views` | required school-owned (`SupportAccessView`) | one row per page viewed: grant, staff member, route name, record parameter and id, the ids of the accounts a list or search showed (`listed`, JSON), time |

Statuses: `pending`, `active`, `declined`, `withdrawn`, `ended`. Shown as: waiting, lapsed (pending past 24 hours), open, expired (active past `expires_at`), declined, withdrawn, ended early. Migration `2026_11_01_130000`; `down` refuses to drop tables that hold rows.

Audit actions (all against the school, all in the HQ audit log): `platform_support_access_requested` (with the reason), `platform_support_access_approved`, `platform_support_access_declined`, `platform_support_access_emergency` (with the reason), `platform_support_access_ended` (side: school or staff), `platform_support_page_viewed` (route and record id).

## Endpoints

| Method | Path | Name | Who |
| --- | --- | --- | --- |
| GET | `/saas/support` | `saas.support.index` | `support.access` |
| POST | `/saas/support` | `saas.support.store` | `support.access` |
| GET | `/saas/support/{grant}` | `saas.support.show` | `support.access` |
| POST | `/saas/support/{grant}/emergency` | `saas.support.emergency` | `support.emergency` |
| POST | `/saas/support/{grant}/end` | `saas.support.end` | `support.access` (requester, or an Owner) |
| GET | `/support-session/{grant}` | `support-session.show` | the requester, grant open |
| GET | `/support-session/{grant}/people` | `support-session.people` | the requester, grant open |
| POST (a read) | `/support-session/{grant}/people` | `support-session.people.search` | the requester, grant open (`SupportSessionSearchRequest`) |
| GET | `/support-session/{grant}/people/{person}` | `support-session.person` | the requester, grant open |
| GET | `/support-access` | `support-access.index` | the school's Super Admin |
| GET | `/support-access/{grant}` | `support-access.show` | the school's Super Admin |
| POST | `/support-access/{grant}/approve`, `/decline`, `/end` | `support-access.approve`, `.decline`, `.end` | the school's Super Admin |

HQ writes use `SupportAccessRequest`, school writes `SupportAccessDecisionRequest`; `SupportAccess` checks the actor and validates again. School panel menu: Settings, School, "Navigate Ed support access" (Super Admin). HQ bar: "Support access". No API v1 or v2 route and no `openapi.yaml` change.

## Acceptance tests (`Platform/SupportAccessTest`)

| Test | Given | When | Then |
| --- | --- | --- | --- |
| `test_support_asks_the_school_approves_every_page_viewed_is_logged_and_the_session_expires` | Support, School A's Super Admin | ask; school approves; open overview, people, a search, one account (with School B's header and school choice); wait past expiry | only the Super Admin is told (delivered in-app and by email); refused before approval; four views logged with route names and the account id, no search words; no membership or role given; School B never shown; HQ grant page and audit log list the pages; the school sees history and pages; after expiry refused and nothing written |
| `test_an_owner_emergency_override_opens_at_once_and_tells_the_school_immediately` | a waiting request | Support overrides; Owner without or with a short reason; Owner with a reason; Owner opens it; override a declined or withdrawn request | 403; validation errors; open at once, the Super Admin told immediately, audited with the reason; the Owner gets 404 (not their grant), the requester sees "Opened by emergency override"; the school sees it and the reason; 409 and nothing written |
| `test_the_school_or_the_staff_member_can_end_it_early` | an open session | the school ends it; the staff member ends another; a waiting request withdrawn | refused afterwards; ending twice is 409; a withdrawn request cannot be approved |
| `test_without_an_open_grant_of_ones_own_nothing_opens_and_nothing_is_written` | no grant, pending, declined, expired, ended, lapsed, another person's | open any session page; approve the lapsed one; signed out | 404, 403 ×3 pages each, 404; 409; sign-in redirect; nothing written |
| `test_a_session_never_reaches_another_school` | an open School A grant | open School B's people, a platform account, a School B grant; search for School B's people; switch school or read across schools inside the session | 404 and nothing written; only School A's results; `set(2)` and `acrossSchools()` throw |
| `test_every_write_in_a_read_only_session_is_refused_server_side` | an open grant and a route that would write | POST, PUT, PATCH, DELETE; a write to the session's own pages; a school panel write | 403 by the middleware; 405; refused (no school); nothing written |
| `test_restricted_records_are_refused_by_their_own_authorities_inside_a_session` | an open grant, the restricted modules on | call wellbeing, risk (both entry points), support plans, counselling, discipline, health forms and learner notes from inside the session; open the panel pages; call the authorities directly | 403 every time, nothing logged; never 200; 403 |
| `test_sales_finance_content_and_school_accounts_cannot_request_or_open_support_access` | Sales, Finance, Content (each with a grant row); Support moved to Sales mid-session; School A's Super Admin and a teacher | every HQ page and action; open the session | 403 and nothing written |
| `test_a_school_cannot_see_or_decide_another_schools_requests_and_only_its_super_admin_decides` | a request to School A | School B's Super Admin lists, opens, approves, declines, ends (also with School B's header); School A's teacher; a platform account with School A's Super Admin role | not listed and 404 ×5; 403; 403; then School A's Super Admin declines and School B's history stays empty |
| `test_requests_are_validated_and_one_open_request_per_school` | Support | a short reason, 480 minutes, a write scope, an unknown or suspended school; a second open request | validation errors and nothing written; 409 |
| `test_a_school_decline_cannot_be_defeated_by_a_new_request_or_an_override` | the school declined a request | anyone asks again in the cool-down; after it, an Owner overrides at once, overrides their own request, opens the form, overrides after 15 minutes; then the school declines again and an Owner overrides another waiting request | 409 ×3 and nothing written; 409; 403; the earlier decline shown; open, the audit records the earlier decline; 409 and nothing written |
| `test_people_search_logs_who_was_listed_and_finds_families_only_by_exact_email` | an open grant | search a staff fragment, a parent's and a learner's fragment, a parent's exact email (other case); the staff list with a query string | Tara found; no family found by a fragment; the parent found by email; the query string ignored; each view logs the listed ids, never the words; the school sees "1 account listed" and "No accounts listed" |
| `test_refused_session_attempts_are_logged_and_cross_site_requests_refused` | a pending and an open grant | open the pending one, an unknown one, and the open one cross-site, then same-origin | 403, 404, 403 and nothing written, then 200; `support_session_refused` logged with grant, user and reason each time |
| `test_an_override_is_refused_when_nobody_at_the_school_can_be_told` | School B has no active Super Admin | ask; open HQ's list and the request; override after 15 minutes | recorded; "Nobody at this school can approve it" shown; 409 and nothing written |
| `test_the_session_lock_is_lifted_only_by_the_request_reset` | a support session | `forget()`; `reset()`; `forget()` without a session; a request after a session | throws and the session holds; cleared; works; the next request starts clean |
| `test_the_requester_can_never_decide_their_own_request` | the requester has since lost platform staff status and become the school's Super Admin | approve, decline | 403 and nothing written |
| `test_restricted_authorities_refuse_a_support_session_without_the_impersonation_flag` | wellbeing, risk (both), support plans, counselling, discipline, learner notes and health authorities, people they let through in the school | call each in a support session whose impersonation flag is forced off | each refuses with 403: the support-session check works on its own |
| `test_open_requests_stay_listed_however_many_old_grants_exist` | 510 old expired or lapsed grants | a new request; HQ's list | the new request is the only open one listed (the open windows are applied in SQL before the 500 limit) |

`SaasPanelTest` counts the `/saas` routes (41) and requires `platform.can:` on each.

## Not in this step

- **Write scopes.** None: every candidate changes a person's account or record and needs its own design (what may change, a second approval, an undo). The `scope` column and config are ready for a named scope; the middleware refuses every write regardless until then.
- **The school's own panel pages in a session.** Sessions show only the fixed support pages above. Opening the school's real screens read-only would need each controller reviewed for GET writes and restricted fields.
- **App access** (v1/v2 API, the Flutter apps) and any API shape: out of scope.
- **The HQ home page tile** "open support access sessions" (it waits for the HQ home page).
- A school-wide banner in the school panel while a session is open (the Super Admins are told, and the page lists it).
- Step-up re-authentication before opening a session. (An Owner can no longer override their own request; a second Owner decides.)
- Notifying the school when a session expires by itself (it is listed as expired).
