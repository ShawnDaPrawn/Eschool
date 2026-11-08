# Learner subscriptions, entitlements and their revenue

Action Plan 3.20 (entitlements and billing) and the subscription part of SA.8
(billing, payments and revenue, Build 3). Owner role: Backend and Admin UX (HQ).
Server side only; the guardian app screens follow.

Daily practice stays free for every learner. The subject tutor and the parent
insights are the paid features. A learner is entitled through a school licence,
a sponsor's place, or the family's own plan.

## Switching it on

Nothing changes for any school until HQ turns the platform feature flag
`mastery_subscriptions` (`config('subscriptions.flag')`) on for it in
`/saas/flags` (beta by named school, then everyone). An unknown or unset flag is
off, so deploying this changes nothing. Where the flag is off, `required` is
false and everyone is entitled (`source: included`).

## Who is entitled (`Entitlements`)

On the school's today, a learner is entitled when any of these is paid for and
its paid period (`current_period_end`, the last day included) has not passed:

- the school's licence (`plan = school_licence`): every learner at the school;
- a sponsor's places (`plan = sponsored`): the learners the school named;
- the learner's own plan (`per_child` or `family`). A cancelled plan keeps its
  paid period.

Where it is wired in:

| Place | What needs the entitlement |
| --- | --- |
| `ModuleGate::allows` / `writable` / `context` | A module marked `paid` in `config/modules.php` (the tutor) for a signed-in learner. The app's module list (`/me/context`) leaves the tutor out, and the `module:tutor` routes answer 404, as for a stage the module does not serve. Guardians, staff and jobs are not limited here. |
| `TutorAccess::learner` | The learner's own tutor (403), for every tutor service called outside the routes. The tutor memory controls stay open (`module: false`). |
| `TutorQueries::parentSummary` | The guardian's tutor summary for a child (403). |
| `MasteryPolicy::family` | A guardian's view of the child: the mastery map and its subjects, the learning path summary and the starting-point summary (403). The learner's own view is not a parent insight and stays free. |

Daily practice (`learning/practice`, practice tests, the learning path itself,
diagnostics for the learner) never asks; the learning path only marks the tutor
step "not available" when the tutor is refused.

## Plans and prices

`config/subscriptions.php`, integer cents, per one-month period:

| Key | Placeholder | Meaning |
| --- | --- | --- |
| `per_child_cents` | 8900 (R 89) | the hypothesis for the first child |
| `additional_child_cents` | 6900 | each further child of the same guardian at the same school |
| `school_licence_per_learner_cents` | 3500 | the default HQ starts from; HQ records the agreed amount |
| `sponsored_place_cents` | 8900 | the default HQ starts from; HQ records the agreed amount |

**Owner's decision needed:** all four are placeholders (`pricing_confirmed` is
false and the API and HQ page say so). A subscription keeps the price it was
asked for. The family price applies while the guardian has another child's plan
requested, running or paid for at the same school; family plans across schools
are not in this step.

## Status lifecycle

`requested` (asked for, not paid) → `active` (a period is paid) →
`cancelled` (by the guardian or the gateway; keeps the paid period) or `ended`
(by HQ, at once, with a reason). A request nobody paid for is `declined` by HQ
with a reason, or `cancelled` by the guardian. `ended` and `declined` are final;
`cancelled` can only become `ended`. A running plan whose paid period passed
without a renewal shows as `lapsed` (`effective_status`); recording the next
EFT starts a new period from that day. The database refuses a change of
reference, plan, learner, guardian or price, a status that moves back, and a
paid period that shrinks.

## Paying (`LearnerSubscriptions::recordPayment`)

One period at a time, for exactly the subscription's price:

- **Manual EFT (now).** HQ Finance (`billing.manage`) records the amount, the
  date received and the bank line's reference on `/saas/subscriptions`. The
  family or sponsor quotes the subscription reference (`NES-000001`) on the EFT,
  so HQ matches by reference and never needs a name.
- **A gateway (later).** See below; it goes through the same method.

The first period starts on the school's today; a renewal starts the day after
the paid period. Each payment is immutable and writes a row in the global
receipt registry `platform_bank_receipts` (by trigger), unique on method and
normalised reference (upper case, single spaces) across every school, because
everyone pays into Navigate Ed's one account. A bank line that already stands
as a school's invoice payment (`platform_payments`, `school_group_payments`) is
refused too, and a school's invoice payment refuses a line registered here.

Why not the school ledger's `financial_receipts`: that registry is per school
and every row is a posting in the school's own ledger. Subscription money is
Navigate Ed's revenue, not the school's, and the same bank line must not be
usable at two schools; so this is the same pattern (immutable, filled by
trigger, unique source reference, checked under lock first) at platform level.

## The gateway seam

`SubscriptionGateway` (`verify(rawBody, headers): ?GatewayEvent`) is the only
thing a provider adapter implements; `GatewayEvent` carries the notice id, the
type (`payment.succeeded`, `subscription.cancelled`), the subscription
reference, the charge id, amount in cents and currency. Notices arrive at
`POST /webhook/subscriptions/{gateway}` (outside CSRF like the Twilio and SES
webhooks, throttled, raw body). Only the gateway named in
`config('subscriptions.gateway')` and listed in `subscriptions.gateways` is
acted on; anything else is 404, a bad signature 401. Each notice is recorded
once in `subscription_gateway_events` in the same transaction as its effect, so
a repeated notice changes nothing. A charge's source reference is
`PROVIDER:CHARGE` in the same registry. An unknown reference or an amount that
is not one period is recorded and ignored (200) so the gateway does not retry.
No adapter ships: the gateway is not chosen.

## Guardian API (v2, `role:Parent`)

| Method | Path | Does |
| --- | --- | --- |
| GET | `/subscriptions` | the plans, each of the guardian's children here with their entitlement and plan, the guardian's own subscriptions, EFT instructions |
| GET | `/subscriptions/learners/{learner}` | the plans and one own child |
| POST | `/subscriptions/learners/{learner}/requests` | ask for a plan (`operation_id`, kept on the subscription; a replay returns it) |
| POST | `/subscriptions/plans/{subscription}/cancel` | cancel own plan (`operation_id`, kept) |

Another guardian's child or plan, or another school's, is 404. A request is
refused (409) where the school includes plans, the child is covered by the
school or a sponsor, or the child already has a plan or a request waiting.
The child must be approved.

## School API (v2, `staff`, the school's Super Admin)

| Method | Path | Does |
| --- | --- | --- |
| GET | `/school/sponsored-places` | the school's sponsorships and the learners named |
| POST | `/school/sponsored-places/{subscription}/learners` | name a learner of this school (`learner_id`); 409 when every place is taken |
| POST | `/school/sponsored-places/{subscription}/learners/{learner}/remove` | free a place |

## HQ (`/saas/subscriptions`)

`billing.view` (Owner, Finance, Operations) reads the page and
`GET /saas/subscriptions/revenue?month=YYYY-MM` (JSON). `billing.manage`
(Owner, Finance) records EFTs, declines, ends, and sets up a school licence or
sponsored places for the school chosen in the filter. Sales, Support and
Content get 403; school accounts never reach `/saas`. Rows show the reference,
school, plan, status, price, paid-until and (for sponsors) how many places are
named: never a learner's or guardian's name. Every change is in the platform
audit log (`platform_subscription_created`, `_payment_recorded`, `_declined`,
`_ended`) and the subscription's own trail (`learner_subscription_events`).

### Revenue (SA.8 Build 3)

For a month: collected (payments received), monthly recurring revenue on the
month's last day (today for this month: plans whose paid period covers it,
price per month) and the annual run rate (x 12), families paying and revenue per
family, paying learners (family plans and sponsored places) and revenue per
learner, churn (plans cancelled or ended in the month against those running on
its first day, in basis points), and the same per plan and per school. Integer
cents; counts only.

## Prompts (`subscription.prompt`, `subscriptions:prompt`)

A new notification type for the parent portal, off by default on push, email
and in-app. Nothing is sent until the school switches it on in its notification
rules (or a guardian does). The daily command (15:20) runs only where the
school's flag and the tutor are on, for approved children who could use the
tutor (by stage), with no plan and no request waiting: at most once every
`prompts.every_days` (30) per guardian and child, or sooner (not within
`prompts.min_gap_days`, 7) when a released result moved the child into the
support band (`progress_band_alerts`, family audience) since the last prompt.
One notice per guardian per run, the same words whatever the reason: it names
no child, subject or mark.

## Data

| Table | Tenancy | Holds |
| --- | --- | --- |
| `learner_subscriptions` | school-owned | reference, plan, learner and guardian (personal plans), sponsor and places, status, price, currency, period, provider and its id, paid period, request and cancel keys, who and when |
| `learner_subscription_members` | school-owned | the learners named for sponsored places, added and removed |
| `learner_subscription_payments` | school-owned, immutable | each paid period: method, provider, source reference, amount, date, period |
| `learner_subscription_events` | school-owned, immutable | the audit trail: event, actor kind and id, status after, amount, ids and dates |
| `learner_subscription_prompts` | school-owned | which guardian was prompted about which child, why, and when |
| `platform_bank_receipts` | global, immutable | one row per bank line or gateway charge: method, reference, amount, payment id |
| `subscription_gateway_events` | global | each gateway notice once: id, type, reference, outcome |

Migration `2026_11_11_310000_create_learner_subscriptions`; its `down` refuses
while any of these tables holds rows.

## Acceptance tests (`Platform/LearnerSubscriptionsTest`)

| Test | Checks |
| --- | --- |
| `test_daily_practice_stays_free_while_the_tutor_and_parent_insights_need_an_entitlement` | without the flag nothing changes; with it, the learner loses the tutor (routes, module list, `TutorAccess`) and the guardian the mastery map, learning path summary and tutor summary, while the learner's own map and daily practice (access, a set, an answer) stay open; a paid plan opens both; the day after the paid period they close again and practice does not |
| `test_a_guardian_asks_for_plans_with_the_family_price_hq_records_the_eft_once_and_cancelling_keeps_the_paid_period` | plans and children; per child then family price; replay by operation id; one bank line once however it is typed, at subscriptions and at school invoices; exact amount; renewal; cancel keeps the period and is replayable; the trail; immutability by model and by SQL |
| `test_a_school_licence_entitles_every_learner_there_and_sponsored_places_only_the_named_learners` | one licence per school; every learner of that school only; ending it; sponsored places for named learners only, a full sponsorship, another school's learner, a teacher refused, moving a place |
| `test_wrong_role_wrong_school_and_wrong_owner_are_refused_and_nothing_is_written` | another guardian, a teacher, a learner, the Super Admin on the guardian API, School B's guardian and School A's guardian in School B; undocumented fields; HQ roles; another school's subscription through this school's URL; a school account on `/saas`; decline with a reason |
| `test_hq_revenue_counts_collected_running_families_and_churn_without_names` | collected, MRR, run rate, families, per family, paying learners, per learner, per plan, per school, churn |
| `test_a_gateway_notice_is_acted_on_only_when_configured_and_signed_and_only_once` | not configured 404, bad signature 401, paid once, the same notice again, the same charge again, the wrong amount, a cancel notice, an unknown reference |
| `test_the_migration_refuses_to_roll_back_once_it_holds_subscriptions` | the rollback refuses with rows |
| `test_prompts_are_off_by_default_follow_the_frequency_and_falling_scores_and_never_mention_marks` | off by default; on through the school's rules; frequency; once a day; scores fell sooner but not within the gap; nothing for an entitled child, a child waiting for payment or a school without the flag; no name, subject or mark in the notice |

Every v2 response in these tests is validated against `openapi.yaml`.

## Not in this step

- The guardian and school app screens (Flutter), and an in-app prompt on sign-in; the API and the notice are ready.
- A payment gateway adapter, its sandbox and card or debit-order checkout: the gateway is not chosen.
- Automatic renewal, proration, refunds and reversing a recorded subscription payment (HQ ends the plan instead).
- Family plans across schools, and repricing a further child's plan when the first child's plan stops.
- Importing or matching bank statements to subscription references.
- VAT on subscription invoices and an accounting export.
- A school panel page for naming sponsored learners (the API is there).
- Per-learner AI spend caps for paid learners (Action Plan 3.1).
