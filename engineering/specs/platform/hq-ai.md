# HQ AI control centre

Action Plan item SA.15. HQ staff need one place to see whether each AI
feature is cleared to run, what each school is spending against its caps, and
whether safeguarding flags are waiting for a school's reviewers, to switch
an AI feature on or off for a school, and to set the platform's AI spending
caps. Until now the only HQ-side AI page was
the dated cost report at `/platform/ai-costs`, outside `/saas`, behind its own
console-issued grant.

The page is `/saas/ai` ("AI control centre" in the platform bar).

## User stories

- As Operations or Support, I see each AI feature's qualification, provider,
  model, price and caps as `config/ai.php` holds them, so I can answer "is the
  tutor live yet, and why not?" without reading the config.
- As Operations or Support, I see each school's AI spend this month and today
  against its daily cap, its per-learner cap, and how many safeguarding flags
  wait in its queue, so I can spot a school near its cap or with reviews
  piling up.
- As an Owner, I switch an AI feature on or off for a school, with a reason
  that is kept in the audit log. I cannot switch on a feature that is not
  qualified.
- As an Owner, I set each AI feature's daily caps per school, per person and
  per learner from HQ, with a reason, without a deploy. The new cap applies
  from the next request, and every change is kept with who made it, when, the
  old and new values and why.
- As a school, my own lower limits at `/ai-settings` still apply after HQ
  changes a cap, and HQ never changes them.
- As a learner, my Subject tutor is a service to me, not to my school: my
  school reaching its AI cap does not stop it, and my use does not eat into the
  school's allowance. My own daily tutor cap still applies.
- As a school, HQ never sees our learners' prompts, the AI's answers, a flag's
  content or who a flag is about: only totals and counts.

## Who can do what

| Role | `ai.view` (the page) | `ai.manage` (the switch) | `ai.caps` (spending caps) |
| --- | --- | --- | --- |
| Owner | yes | yes | yes |
| Operations | yes | no | no (403) |
| Support | yes | no | no (403) |
| Finance, Content, Sales | no (403) | no (403) | no (403) |

`ai.view` is added to Operations and Support in `config/platform_roles.php`;
Owner holds every ability.

**Why a new `ai.caps` and not `ai.manage`.** Both are Owner-only today, but they
are different decisions: `ai.manage` decides whether a school may use a
feature; `ai.caps` decides how much money the platform lets every school spend
on it. Keeping them apart means that if switching features is ever delegated
(say to Operations, for onboarding), spending authority does not move with it
by accident. No role but Owner lists `ai.caps`. A school account (including a school's own Super
Admin) is refused by `platform.staff` before any ability is checked.

## What the page shows

All of it is read from configuration and aggregate columns. The page never
reads `ai_usage.inference_output`, `inference_sources`, `user_id` or
`learner_id`, a flag's code or snapshot, or any learner record.

- **Providers** (`ai.providers`): name, qualification (`qualified`), whether a
  key is set (never the key), moderation model, the features using it.
- **Features** (`ai.inference.features`):
  - **Qualification** is the feature's provider's `qualified` flag in
    `config/ai.php`. Nothing else qualifies a feature. With nothing recorded
    (today every provider is `qualified => false`) the page says
    "Not qualified".
  - Provider and model as configured ("no model set" when the model is empty).
  - Whether the feature is switched on for the platform (`ai.inference.enabled`
    and the feature's `enabled`). When the platform switch is off, the page
    says that no AI feature answers anywhere, whatever a school switched on.
  - Whether a price is set (`cents_per_million_tokens`).
  - **Evaluation**: results of `ai:eval` are not stored anywhere, so the page
    says "No evaluation result recorded". It shows how many cases the feature's
    corpus (`resources/ai/evals/<feature>.jsonl`) holds (a count, never the
    cases) and the pass marks in `ai.evaluation.minimum`.
  - The daily caps per school, per person and per learner **in force**, in
    cents, marked "set in HQ" when HQ saved the value (otherwise it is the
    `config/ai.php` default), and the token caps from config. A personal service
    (the Subject tutor) shows "Not counted: a personal service" for the school
    cap.
  - The school module the feature runs in (`ai.inference.school_modules`).
- **Schools** (50 a page, by name):
  - **This month** and **today**, by the school's own clock (as the gateway
    writes `ai_usage.usage_date`): actual plus reserved charges
    (`cost_cents + reserved_cost_cents`), integer cents, of the school's own
    features only. Subject tutor spend is not in these figures.
  - **Daily cap.** The gateway checks a school's day of school-feature spend
    against the cap in force for the feature in use, lowered by the school's own
    limit in `ai_school_limits` (`/ai-settings`). The page shows the highest
    school-feature cap in force, lowered by the school's own
    `school_daily_cents` when set, today's school spend against it as a
    percentage, and "Cap reached" at or above it.
  - **Daily cap per learner**: the highest school-feature `learner_daily_cents`
    in force, lowered by the school's own `learner_daily_cents`.
  - **Subject tutor (personal)**: today's and this month's tutor spend, shown
    apart, and the tutor's per-learner cap in force (lowered by the school's own
    `learner_daily_cents`). It is never compared with the school's cap.
  - Whether the school paused AI itself, and whether it set its own limits.
  - **Safeguarding queue**: the number of flags with review status `open` and
    `escalated`, for the features named in `config('ai.quarantine.participants')`,
    counting only flags a school reviewer can still open (inside the retention
    window, or with an unexpired snapshot), as `AiFlagReviews` lists them.
    Counts only.
  - **AI features switched on**: the state of each AI module in that school
    (`ModuleGate::state`).
- A link to the dated cost report, shown only to someone who holds an active
  `platform_ai_cost_grants` grant.

- **Spending caps**: the editor (Owners only) and the latest 20 cap changes,
  for everyone who can open the page (see below).

### Currency

The gateway meters in `ai.billing_currency`, which is **USD** today, and caps
are cents in that currency. The page shows amounts in the metering currency
("US$ 6.00"; "R 6.00" when the currency is ZAR) and never converts: there is
no exchange rate in the system, and inventing one would misstate spend against
caps. Rands appear as soon as the billing currency is set to ZAR. Receipts with
no recorded currency (before the currency column) are shown separately as
"(currency not recorded)" and never counted against the cap, as the gateway
also refuses to mix them.

## Switching a feature for a school

`PUT /saas/ai/switch` with `school_id`, `module`, `enabled` and `reason`.

- Per-school AI switches already exist: each AI feature runs in a school
  module (`config/modules.php`): School assistant and Message translation in
  `ask_school`, and `teaching_assistant`, `exam_coach` and `tutor` in their own.
  The switch is the same `school_modules` row the school's own module page
  and HQ's module controls write, through `ModuleGate::change` (package and
  dependency rules included). No parallel switch was added. The
  feature-to-module map is `ai.inference.school_modules`.
- Only AI modules can be switched here; any other module is a validation error.
- **Qualification.** Switching on is refused (a validation error on `module`:
  422 for JSON, the message on the form) while any AI feature in that module is
  not qualified. `ask_school` needs both of its features qualified. Switching
  off is always allowed.
- The other two module switches (the school's own `/school-modules` and HQ's
  `schools.modules`) are unchanged: `ask_school` also carries human support
  that works without AI. They cannot make an unqualified feature answer: the
  gateway (`ProviderTransport`) refuses every call to an unqualified provider,
  and inference stays off while `ai.inference.enabled` is false.
- A reason of 10 to 500 characters is required. The form asks not to name a
  learner, parent or staff member.
- Switching to the state the module is already in changes nothing and writes
  no audit row.
- Writes go through `AiFeatureSwitchRequest` (`ai.manage`) and
  `PlatformAiControls::switch`, which checks `ai.manage` and validates again.

### Audit

`platform_ai_feature_switched` ("AI feature switched" in the HQ audit log),
recorded against the school: before `{enabled}`, after `{module, features,
enabled, reason, changed_by}`. The reason is the only free text, as for a
school status change. SA.12's incident register keeps no free text in the
audit at all because its summaries may describe people; a switch reason is a
short operational note kept with the change, and the HQ audit log shows it as
the "why".

## Spending caps set in HQ

Owner decision, 31 October 2026: AI spending caps are set in HQ, not
hard-coded. `PUT /saas/ai/caps` with `feature`, `school_daily_cents`,
`actor_daily_cents`, `learner_daily_cents` and `reason`.

- **Defaults.** Each feature's `school_daily_cents`, `actor_daily_cents` and
  `learner_daily_cents` in `config/ai.php` are the defaults. They stay in force
  until HQ saves a value for that cap. A blank cap on the form goes back to the
  default (the saved value is removed).
- **In force at once.** The gateway reads HQ's saved caps afresh on every call
  (`PlatformAiCaps::apply`, inside `InferenceGateway::configuration`), so a
  save applies from the next request. A request already running sees the
  changed configuration at its next recheck and stops with
  `inference_context_changed`, as it does when a school changes its own limit.
- **The school's own limit still applies on top.** After HQ's caps replace the
  defaults, `AiSchoolControls::apply` lowers each cap to the school's own value
  in `ai_school_limits` when it set one. The effective cap is the lower of the
  two. HQ never reads into or writes `ai_school_limits`: an HQ edit cannot
  overwrite or clear a school's own setting. HQ caps are platform-wide; there
  are no per-school HQ overrides.
- **Validation** (the `AiCapsRequest` FormRequest, then the service again):
  feature one of `ai.inference.features`; each cap blank or an integer number of
  cents, at least 0; at most 1,000,000 cents (10,000.00) for the school cap and
  100,000 cents (1,000.00) for the per-person and per-learner caps; reason 10 to
  500 characters. Zero is a cap: that budget is closed for the day. A personal
  feature has no school cap, so sending one is a validation error.
- **No change, no write.** A cap submitted at the value already in force (saved
  or default) changes nothing; if no cap changed, nothing is written and there
  is no audit row.
- **Currency.** Amounts stay in `ai.billing_currency` (USD today). The editor
  labels every field "cents, USD" (the current code) and shows each default and
  maximum as money. No exchange rate is applied.
- **History.** Every changed cap writes a `platform_ai_cap_changes` row: feature,
  cap, old cents (null when the default was in force), new cents (null when it
  went back to the default), the default at the time, currency, reason, who and
  when. The page lists the latest 20.
- **Audit.** One `platform_ai_caps_changed` row per save ("AI spending caps
  changed" in the HQ audit log), with no school: before `{feature, <cap>:
  cents}`, after `{feature, <cap>: cents, currency, reason, changed_by}`. Only
  the caps that changed are listed. No key, model, prompt or school value is
  ever recorded.
- Writes go through `AiCapsRequest` (`ai.caps`) and `PlatformAiCaps::save`, which
  checks `ai.caps` before validating and again inside the transaction, and
  locks the feature's saved rows while it writes.

Token caps (`*_daily_tokens`) and the per-feature output limit stay in
`config/ai.php`.

## The Subject tutor is a personal service

Owner decision, 31 October 2026: the AI tutor in Mastery is a personal service
to one learner, not a school service, so it does not count against the school's
AI cap. `ai.inference.personal_features` lists the personal features; only
`tutor` belongs to the tutor in `config/ai.php` (the tutor calls the gateway
with no other feature), so it is the only one.

The gateway keeps two pools of the day's metered spend:

- **A school feature** (School assistant, Message translation, Teaching
  assistant, Exam coach) checks the school, per-person and per-learner caps
  against school-feature spend only. Tutor receipts are left out, so the tutor
  never uses up a school's allowance, a person's or a learner's.
- **A personal feature** (the tutor) has no school check at all: a school at
  its cap can still use the tutor. Its per-person and per-learner caps are
  checked against tutor spend only, and its receipts are not added to the
  school total.
- Both pools are still metered in `ai_usage` with the school's id, as before.
  A day's receipts in an unknown or different currency still block every
  feature (the currency check spans the whole day).
- The school still controls the tutor: switching the `tutor` module off,
  pausing AI at `/ai-settings`, and a lower own `learner_daily_cents`
  (which lowers the tutor's per-learner cap too) all still apply.
- The tutor's per-person and per-learner caps are set in HQ like any other
  feature's.

This changes `engineering/specs/ai/gateway-foundation.md`, which said school
and actor limits span all inference features: they now span all school
features, and the tutor has its own pool.

## The old `/platform/ai-costs` page

Kept as it is: same route, same name (`ai-platform.costs`), same
`platform_ai_cost_grants` check, same tests. It is not redirected. Reasons:

- Its grant is separate on purpose (issued from the console, time-limited) and
  is not tied to an HQ role. Redirecting grant holders to `/saas/ai` would lock
  out a holder without `ai.view`, and letting `ai.view` reach the dated report
  would widen a grant that was designed to be narrow.
- The control centre shows this month and today; the dated report covers any
  range up to a year, by currency.

HQ links to it for anyone holding an active grant. Merging the two (moving the
dated range into `/saas/ai` under an HQ ability and retiring the console
grant) is a later decision for the owner.

## Data

Migration `2026_11_03_090000_create_platform_ai_caps`, two platform tables (no
`school_id`; sorted as global in `SchoolOwnedTables`):

- `platform_ai_caps`: `feature`, `cap` (`school_daily_cents`,
  `actor_daily_cents` or `learner_daily_cents`), `cents` (unsigned integer),
  `updated_by`, timestamps; unique (`feature`, `cap`). One row per cap HQ set.
- `platform_ai_cap_changes`: `feature`, `cap`, `old_cents`, `new_cents` (both
  nullable: null means the config default), `default_cents`, `currency`,
  `reason`, `changed_by`, `created_at`. Append only.

`down()` refuses to drop either table while it holds rows.

Read: `ai_usage` (aggregate columns, and `feature` to split tutor spend),
`ai_school_limits`, `ai_flags` with `ai_usage.feature` and
`ai_quarantine_snapshots` (counts), `schools`, `school_modules`,
`platform_ai_caps`, `platform_ai_cap_changes`. Written: `school_modules`
(through `ModuleGate`), `platform_ai_caps`, `platform_ai_cap_changes` and
`audit_log`.

Config: `ai.inference.school_modules` (the feature-to-module map);
`ai.inference.personal_features` (new, `['tutor']`); `ai.view`, `ai.manage` and
`ai.caps` in `config/platform_roles.php`.

## Acceptance tests (`Platform/HqAiControlsTest`)

| Test | Given | When | Then |
| --- | --- | --- | --- |
| `test_the_page_shows_qualification_caps_spend_and_queue_counts_from_metering` | Support; metered receipts in School A (this month, today, last month, one with no currency) with its own limits, and in School B; open, escalated, reviewed and expired flags | open `/saas/ai` | School A: this month US$ 4.00 and 0.40 (currency not recorded), today US$ 1.50 of US$ 6.00 (25%), learner cap US$ 0.20, 1 waiting and 1 escalated; School B: US$ 7.77 of US$ 10.00 (77%), 3 waiting; "Not qualified", "No evaluation result recorded", corpus count; no prompt, answer, source or flag code on the page; no switch form for Support |
| `test_school_b_spend_is_never_attributed_to_school_a` | spend and a flag in School B only | open the page | School A shows nothing metered, US$ 0.00 today and no flags; School B shows its own total |
| `test_owner_switches_a_qualified_feature_on_and_off_with_an_audited_reason` | an Owner; the provider qualified | switch the tutor on for School A, again, then off | School A's `tutor` module is on then off; School B unchanged; the repeat changes nothing; two audit rows against School A whose only free text is the reason; the HQ audit log shows "AI feature switched" |
| `test_switching_on_an_unqualified_feature_is_refused` | an Owner; nothing qualified | switch the tutor on (form and JSON); `ask_school` with one of its two features qualified; a non-AI module; an unknown school; no reason | form error on `module`; 422 each time; nothing written, no audit |
| `test_wrong_roles_are_refused_and_nothing_changes` | Sales, Finance, Content; Support, Operations | open the page; switch (HTTP and the service) | no menu link and 403 on both for the first three; the last two see the page and get 403 on the switch; `school_modules` and the audit unchanged |
| `test_a_school_account_is_refused` | a school Super Admin and a teacher | open the page; switch | 403; nothing written |
| `test_the_dated_cost_report_stays_behind_its_own_grant_and_is_linked_for_holders` | Support with and without a cost grant | open `/saas/ai` and `/platform/ai-costs` | without: no link, 403 on the report; with: the link and the report |

### Spending caps (`Platform/HqAiCapsTest`)

| Test | Given | When | Then |
| --- | --- | --- | --- |
| `test_owner_sets_caps_that_apply_from_the_next_request_with_history_and_audit` | an Owner; nothing saved | open the page; save School assistant caps 2500 and 75 (learner at its default); blank the school cap; save the values in force again; save the tutor's per-learner cap | the editor shows "cents, USD"; 2500 and 75 in force at once, learner stays the default, other features unchanged; two history rows (old null, default 1000 and 50, USD, reason, who); "US$ 25.00", "set in HQ" and the reason on the page; the blank goes back to 1000 (history 2500 → null); the repeat writes nothing; three audit rows with no school, "AI spending caps changed" in the HQ audit log |
| `test_invalid_caps_are_refused_and_nothing_is_written` | an Owner | −1, 12.5, "ten", above each maximum, no reason, an unknown feature, a school cap for the tutor | 422 on that field each time; nothing written; then 0 is accepted and closes that budget |
| `test_every_role_except_owner_is_refused_and_nothing_is_written` | Operations, Support, Finance, Content, Sales | save caps by form, JSON and the service | 403 each time; Operations and Support see the caps but no editor; caps, history, audit and `ai_school_limits` unchanged |
| `test_school_accounts_are_refused` | a school Super Admin and a teacher | save caps by HTTP and the service | 403; nothing written |
| `test_the_lower_of_hq_and_the_schools_own_limit_applies_and_hq_never_overwrites_a_school` | School A's own limits 600 and 20; School B none | HQ sets 2000 and 80, then 400 | School A keeps 600 and 20, School B gets 2000 and 80 (page: US$ 6.00 and US$ 20.00); at 400 both get 400; both schools' `ai_school_limits` rows unchanged and School A's limit never reaches School B |
| `test_the_audit_row_holds_no_secrets` | a synthetic provider key in config | save a cap | the audit row's keys are exactly feature and the cap (before) and feature, the cap, currency, reason, changed_by (after); no key, app key, provider class, model or prompt anywhere in the row; neither page shows the key |

### The tutor is personal (`Learning/TutorPersonalSpendTest`)

| Test | Given | When | Then |
| --- | --- | --- | --- |
| `test_a_school_at_its_cap_can_still_use_the_tutor_within_the_learner_cap` | School A has spent its 1000-cent cap on a school feature | a school request; a tutor turn; a school request again | the school request is refused (`school_daily_cap`); the tutor turn completes and is metered; the school is still at its cap |
| `test_tutor_spend_does_not_move_the_schools_remaining_allowance` | a tutor turn and 50 dollars of tutor spend today; a one-cent school cap | two school requests | the first is allowed (the whole cent was free), the second is refused; school spend is that one cent |
| `test_the_per_learner_tutor_cap_set_in_hq_still_blocks` | HQ sets the tutor's per-learner cap to 0, then 10 | tutor turns; 10 cents of Exam coach spend for the learner; then 10 cents of tutor spend | refused with `learner_daily_cap` and no provider call; then allowed (school-feature spend is not tutor spend); then refused |
| `test_the_schools_own_lower_learner_limit_applies_to_the_tutor_on_top_of_hq` | HQ 500; the school's own learner limit 0, then 600 | tutor turns | refused, then allowed (HQ's 500 is the lower) |

`Platform/HqAiControlsTest`'s first test now seeds tutor spend beside school
spend: School A's 9.00 of tutor spend today is shown under "Subject tutor
(personal)" and does not reach its cap.

The existing `Ai/AiOperationsTest` (the cost report and its grant) still passes
unchanged. `SaasPanelTest` counts the three new `/saas` routes.

## Endpoints (HQ panel)

| Method | Path | Name | Ability |
| --- | --- | --- | --- |
| GET | `/saas/ai` | `saas.ai` | `ai.view` |
| PUT | `/saas/ai/switch` | `saas.ai.switch` | `ai.manage` (throttled 30 a minute) |
| PUT | `/saas/ai/caps` | `saas.ai.caps` | `ai.caps` (throttled 30 a minute) |

No API or OpenAPI change.

## Not in this step

- **Per-school HQ caps.** HQ's caps apply to every school; a cap for one
  school only is not built.
- **Token caps and the output limit from HQ.** They stay in `config/ai.php`.
- **A stale-edit check on the caps form.** Two Owners saving at once both
  succeed in turn (the rows are locked while one writes) and both changes are
  in the history; the second is not warned that the first changed the value.
- **Who pays for personal tutor spend.** It is metered with the school's id as
  before; billing it to a family is not built.
- **Recording evaluation results.** `ai:eval` prints its score and stores
  nothing; storing results (and showing them here) needs its own table and a
  decision on what counts as qualification evidence.
- **Recording qualification from HQ.** Qualification stays a config change.
- **Rands while metering is in USD.** No exchange rate is applied (see
  Currency).
- **Merging the dated cost report into HQ** (see the old page).
- A monthly cap: the gateway's caps are daily; the page shows the month's
  total without a monthly limit.
