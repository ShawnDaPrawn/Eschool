# School groups: one owner, many schools

Action Plan 5.7 (school groups) and SA.18 (school groups in HQ). Owner roles: Backend, Admin UX (HQ) and Test.

A private school group owns several schools and buys NavigateEd for all of them at once. HQ creates the group and invites its schools. Each school decides for itself whether to join, because the group's administrators will see figures about it next to the group's other schools. The group gets one invoice a month that rolls up its schools' own monthly invoice lines.

## User stories

- **HQ Operations:** I create a group, invite schools to it, name its administrators, and remove a school or revoke an administrator, with a reason each time.
- **HQ Finance:** I decide, for each school in a group, whether it is billed on its own or through the group. I create the group's monthly draft, recompute it, issue it, record the group's EFT payments and reverse a mistaken one.
- **A school's Super Admin:** I see an invitation with exactly what the group will see. I agree or decline, and I can leave at any time. When my school leaves, the group stops seeing it at once.
- **A group administrator** (often the group's owner or CEO): I sign in and see each school's learners, weekly active families, guardian reach, learners active in the app this week and recorded attendance, plus the group's totals. I never see a learner, guardian or staff member, and the role gives me nothing inside any school.
- **Navigate Ed staff:** I see the same suppressed figures as the group, never more.

## Rules

### Groups, schools and agreement

- A group is a platform row (`school_groups`), written only from `/saas`.
- A school is invited by HQ (`school_group_members`, status `invited`). HQ invites; the school decides.
  - Its Super Admin agrees on the school's **School groups** page (`/school-groups`) after reading the agreement text (`SchoolGroups::agreementText`).
  - Agreeing records who agreed, when, the wording's version (`config('school_groups.agreement_version')`) and a SHA-256 of the version and the English text with the group's name, then sets the status to `active`.
  - The Super Admin may decline instead (`declined`). Either way, nothing is shared until they agree.
- The same check as support access decides who counts as the school's Super Admin (`SupportAccess::schoolAdmin`). It refuses a support session, impersonation, cross-school context, platform staff, and a forced password change.
- A school has at most one open membership (`invited` or `active`) in any group. A unique index on `(school_id, open_key)` enforces it: `open_key` is 1 while the membership is open, and null after.
- **Leaving** (the school, `left`) or **removal** (HQ, `removed`, reason required) closes the membership. HQ can also withdraw an invitation (`withdrawn`). Closing a membership also releases the school from any group draft (see "Never both").

### Group-level roles

- `config/school_groups.php` `roles`: the group-level layer over the module and permission map (1.12). The one role is `admin`, with the ability `group.dashboard`. Grants are rows of `school_group_admins` with a reason. Revoking a grant keeps the row.
- **A group role grants nothing inside a school.** It adds no membership, role or permission. A group administrator who also works at one of the schools reaches that school only through their own role there, and reaches the other schools not at all.
- `SchoolGroupAccess` reads grants from fresh rows on every request, so a revoked grant, a closed group or a disabled account stops at once. Platform staff are never group administrators: HQ refuses to name one, and an active platform identity grants no group.
- An account with no school role that holds a grant signs in to `/group`. Anyone else without a school role is still refused at sign-in.

### The dashboard and small numbers

- `SchoolGroupDashboard::read` reads only schools whose membership is `active` now and that are active on the platform. It reads them on every request, so a school that left, or one never in the group, is never read.
- Each school's figures are computed inside that school, from its own rows. They are the ones HQ school health and the leadership dashboard already compute:
  - learners (`PlatformSchoolHealth::familyFigures`);
  - weekly active families and guardian reach (the same rules as HQ school health and Family reach, 2.6);
  - learners active in the app in the last 7 days (`learner_activity_days`);
  - recorded attendance over the last `attendance_days` (28) days of the current school year (`LeadershipDashboard::attendanceTotals`, the same counting as the leadership dashboard, with no split by grade).
- **Small-number suppression** (`SmallNumbers`, k = `config('school_groups.min_count')`, 10 by default):
  - a count of people from 1 to k − 1 shows as "Fewer than k"; 0 and k or more are shown;
  - a share is hidden, with its count, when its base is under k, or when the people it counts or the people it leaves out number 1 to k − 1 (so "all but three" is hidden as well as "three");
  - attendance is hidden when the learners it covers are under k;
  - a group total is hidden when any school's figure for that measure is hidden, so a hidden figure cannot be worked out by subtraction.
- No page lists or names a learner, guardian or staff member, and the pages link to no record.

### Group billing

- **Who:** `billing.view` reads a group's invoices; `billing.manage` changes a school's billing route and works the group's invoices. The rules are the SA.8 ones (hq-billing.md):
  - integer cents throughout, rands typed as text;
  - VAT rounded on each line, with the rate kept on the invoice;
  - drafts can be recomputed or discarded; an issued invoice never changes;
  - references come from the same yearly sequence as school invoices (`NE-2026-0001`);
  - EFT payments are reversed with a reason, never deleted;
  - every step is audited.
- **The draft** (`SchoolGroupBilling::compose`, one per group and month):
  - It takes each school whose active membership says "billed through the group" and that is active on the platform.
  - If the school has no draft of its own for the month, one is made (`PlatformBilling::draftSchool`). The school's draft is then counted and priced again, and its lines are copied onto the group draft.
  - A school whose own month is already issued alone, discarded, or on another group's draft is left off, and the audit lists it.
  - A school without a price blocks the group draft until a price or contract exists and the draft is recomputed.
- **Never both.** A school's month is billed alone or through the group, never both:
  1. `PlatformBilling::issueProblem` refuses to issue alone a school whose active membership says "through the group", or whose draft a group draft holds. A month's issue run therefore leaves those drafts as they are (counted as `left`).
  2. A group draft holds a school's own draft through a claim row (`school_group_invoice_schools`). Unique indexes allow one standing claim per school and month, and per school invoice.
  3. Issuing the group invoice locks each school's own draft. It checks that the draft is unchanged since it was copied and that the membership still says "through the group". It then sets the school's draft to the new status `grouped`. A grouped invoice is frozen like an issued one (the existing model guard and database trigger refuse any change to a non-draft invoice). It is never issued alone, and its month slot stays taken, so no second invoice is ever made for that school and month.
  4. When a school leaves or is removed, its claims on group drafts are released and those drafts' totals worked out again. Its own draft can then be billed alone. A claim on an issued group invoice stays: that month is billed.
  5. Discarding a group draft releases its claims.
- **Immutability below the models:** database triggers (migration `2026_11_08_100000`, MySQL and MariaDB) refuse three things:
  - any change to an issued or discarded group invoice, or its deletion;
  - any change to the lines or claims of a group invoice that is not a draft;
  - any change to a group payment other than one reversal, and any deletion of a payment.

### Audit

`platform_school_group_created`, `platform_school_group_invited`, `platform_school_group_joined` (with the agreement's version and hash), `platform_school_group_declined`, `platform_school_group_withdrawn`, `platform_school_group_left`, `platform_school_group_removed`, `platform_school_group_billing_changed`, `platform_school_group_admin_granted`, `platform_school_group_admin_revoked`, `platform_group_invoice_drafted`, `platform_group_invoice_recomputed`, `platform_group_invoice_issued`, `platform_invoice_grouped` (one row per school, in that school's history), `platform_group_invoice_discarded`, `platform_group_payment_recorded` and `platform_group_payment_reversed`. Membership events carry the school.

## Data

| Table | Tenancy | Holds |
| --- | --- | --- |
| `school_groups` | global | name, status, HQ notes, who created it |
| `school_group_members` | school-owned (required) | the group, status, open key, billing route (`school` or `group`), the invitation (who, when, note), the agreement (who, when, wording version, hash), the end (when, who, which side, reason) |
| `school_group_admins` | global | the group, the person, role, open key, who granted it and why, revocation |
| `school_group_invoices` | global | the group, status, open key, reference, month, period, currency, VAT rate in basis points, schools, learners, subtotal, VAT and total in cents, why a draft is blocked, issue and due dates, who computed, issued or discarded it |
| `school_group_invoice_schools` | school-owned (required) | the claim on one school's own monthly draft: the group invoice, the school invoice, month, open key, learners and totals as copied, when the school's draft was computed |
| `school_group_invoice_lines` | school-owned (required) | each line copied from a school's own draft |
| `school_group_payments` | global | the group's EFT payments against a group invoice, and their reversal |

`platform_invoices.status` gains `grouped`. The migration's `down()` refuses once it holds groups, memberships, invoices or payments.

## Endpoints

HQ (`/saas`, platform staff with two-factor passed, `platform.can:` on each):

| Method | Path | Name | Ability |
| --- | --- | --- | --- |
| GET | `/saas/groups` | `saas.groups.index` | `groups.view` |
| POST | `/saas/groups` | `saas.groups.store` | `groups.manage` |
| GET | `/saas/groups/{group}` | `saas.groups.show` | `groups.view` |
| POST | `/saas/groups/{group}/schools` | `saas.groups.schools.store` | `groups.manage` |
| POST | `/saas/groups/{group}/schools/{member}/end` | `saas.groups.schools.end` | `groups.manage` |
| PUT | `/saas/groups/{group}/schools/{member}/billing` | `saas.groups.schools.billing` | `billing.manage` |
| POST | `/saas/groups/{group}/admins` | `saas.groups.admins.store` | `groups.manage` |
| POST | `/saas/groups/{group}/admins/{admin}/revoke` | `saas.groups.admins.revoke` | `groups.manage` |
| GET | `/saas/groups/{group}/invoices` | `saas.groups.invoices` | `billing.view` |
| GET | `/saas/groups/{group}/invoices/{invoice}` | `saas.groups.invoices.show` | `billing.view` |
| POST | `/saas/groups/{group}/invoices` | `saas.groups.invoices.store` | `billing.manage` |
| POST | `/saas/groups/{group}/invoices/{invoice}/recompute` | `saas.groups.invoices.recompute` | `billing.manage` |
| POST | `/saas/groups/{group}/invoices/{invoice}/issue` | `saas.groups.invoices.issue` | `billing.manage` |
| POST | `/saas/groups/{group}/invoices/{invoice}/discard` | `saas.groups.invoices.discard` | `billing.manage` |
| POST | `/saas/groups/{group}/invoices/{invoice}/payments` | `saas.groups.invoices.payments.store` | `billing.manage` |
| POST | `/saas/groups/{group}/invoices/{invoice}/payments/{payment}/reverse` | `saas.groups.invoices.payments.reverse` | `billing.manage` |

`groups.view` is held by Owner, Operations and Finance; `groups.manage` by Owner and Operations. Writes are throttled. `SchoolGroupRequest` checks the ability, and the services check it again.

School panel (`auth`, `school.context`; the school's Super Admin): `GET /school-groups`, and `POST /school-groups/{member}/agree`, `/decline` and `/leave`. Another school's membership id is not found (the school scope).

Group administrators (`auth`, no `school.context`): `GET /group` (their groups; straight to the group when there is one) and `GET /group/{group}`. Both answer 403 for anyone without a grant, and for an unknown group. No API route or `openapi.yaml` change.

## Screens

- HQ: a "School groups" link in the platform bar. The group list, and the group page with its figures (the same suppressed figures the group sees), its schools and agreements, billing routes and administrators. The group's invoices, and a printable group invoice laid out school by school.
- School panel: **School groups** under settings (Super Admin), in Material 3: invitations with the agreement text, the current group with a Leave button, and the history.
- Group dashboard: a plain Material 3 shell with no school menus. Tiles for the group's totals and a table for each school, with the suppression rule and the definitions written out.
- Strings on the school and group pages are in `en`, `af` and `zu` (and `ar` mirrors `en`, as elsewhere). HQ pages stay in English, like the rest of `/saas`.

## Acceptance tests (`Platform/SchoolGroupsTest`)

| Test | Checks |
| --- | --- |
| `test_hq_creates_a_group_and_a_school_joins_only_when_its_super_admin_agrees` | the happy path from HQ to the school's agreement, which records who, when, the version and the hash; one open membership per school; declining shares nothing |
| `test_a_group_administrator_sees_counts_across_the_current_schools_and_never_a_person` | the figures per school and for the group; a role-free owner's sign-in lands on `/group`; no name, email address or learner number on the page, and no link to a record |
| `test_a_school_outside_the_group_is_never_visible` | an invited school that has not answered, another group, an unknown group and a suspended school are never shown |
| `test_wrong_roles_are_refused_and_nothing_is_written` | Sales, Content and Support are refused; Finance cannot manage groups and Operations cannot bill them; school accounts are refused in `/saas`; a teacher cannot answer for the school; nothing is written |
| `test_one_school_cannot_answer_or_leave_for_another` | School B's Super Admin cannot use School A's ids, and HQ cannot act through another group's URL |
| `test_a_group_administrator_never_opens_a_learner_record` | panel and API learner reads are refused; a group administrator who teaches at School A gets nothing at School B; platform staff cannot be group administrators |
| `test_a_school_that_leaves_drops_out_of_the_groups_view_at_once` | leaving, removal and a revoked administrator take effect on the next request |
| `test_small_numbers_are_hidden_per_school_and_in_the_groups_totals` | the suppression rules, secondary suppression of totals, and HQ seeing the same |
| `test_a_group_invoice_rolls_up_the_schools_lines_and_no_month_is_billed_twice` | roll-up totals; schools not issued alone; the grouped status; reruns change nothing; immutability in the models and the database; payments and their reversal |
| `test_a_school_billed_alone_or_leaving_is_never_on_the_group_invoice_as_well` | a month issued alone stays off the group invoice; leaving releases the claim; a changed school draft needs a recompute before issue |
| `test_hq_pages_use_two_factor_and_show_counts_only` | the HQ pages need two-factor (`PassesPlatformTwoFactor`) and show no person |

`Platform/SaasPanelTest` counts 139 `/saas` routes, each naming its ability. `Tenancy/SchoolOwnedTablesTest` checks the new tables' places.

## Security notes

- The group's view of a school depends on an active membership the school agreed to, read on every request. Nothing about a school is copied into a group table except its invoice lines, and only HQ sees those.
- The dashboard works through `CurrentSchool::run` for each school. Every query in it names the school, and the figures are counts only.
- HQ sees groups through `/saas` with the same suppressed figures. The only way into a school's records is still audited, consented support access (SA.9).

## Not in this step

- Group administrators reading their own invoices and statements: only HQ sees group invoices, as only HQ sees school invoices today.
- Credit notes, overdue reminders and a grace period for group invoices, and allocating a group payment to its schools' months. A group invoice is corrected by discarding its draft before issue; after issue, corrections wait for a group credit note.
- A notice to the school's Super Admins when HQ invites the school. HQ tells the school, and the invitation waits on the School groups page.
- Inviting a group administrator who has no NavigateEd account yet: the person needs an account first.
- Closing or renaming a group.
- More measures on the group dashboard (fees collected, academic risk, discipline counts), trends over time, and a per-grade split. The per-grade split needs suppression designed for small grades first.
- App screens (Flutter): the group dashboard is a web page only.
