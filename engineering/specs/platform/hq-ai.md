# HQ AI control centre

Action Plan item SA.15. HQ staff need one place to see whether each AI
feature is cleared to run, what each school is spending against its caps, and
whether safeguarding flags are waiting for a school's reviewers, and to switch
an AI feature on or off for a school. Until now the only HQ-side AI page was
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
- As a school, HQ never sees our learners' prompts, the AI's answers, a flag's
  content or who a flag is about: only totals and counts.

## Who can do what

| Role | `ai.view` (the page) | `ai.manage` (the switch) |
| --- | --- | --- |
| Owner | yes | yes |
| Operations | yes | no |
| Support | yes | no |
| Finance, Content, Sales | no (403) | no (403) |

`ai.view` is added to Operations and Support in `config/platform_roles.php`;
Owner holds every ability. A school account (including a school's own Super
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
  - The daily caps per school, per person and per learner, in cents and tokens.
  - The school module the feature runs in (`ai.inference.school_modules`).
- **Schools** (50 a page, by name):
  - **This month** and **today**, by the school's own clock (as the gateway
    writes `ai_usage.usage_date`): actual plus reserved charges
    (`cost_cents + reserved_cost_cents`), integer cents.
  - **Daily cap.** The gateway checks a school's whole day's spend against the
    cap of the feature in use, lowered by the school's own limit in
    `ai_school_limits` (`/ai-settings`). The page shows the highest feature cap,
    lowered by the school's own `school_daily_cents` when set, today's spend
    against it as a percentage, and "Cap reached" at or above it.
  - **Daily cap per learner**: the highest feature `learner_daily_cents`,
    lowered by the school's own `learner_daily_cents`.
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

No new table and no migration. Read: `ai_usage` (aggregate columns),
`ai_school_limits`, `ai_flags` with `ai_usage.feature` and
`ai_quarantine_snapshots` (counts), `schools`, `school_modules`. Written:
`school_modules` (through `ModuleGate`) and `audit_log`.

Config: `ai.inference.school_modules` (new, the feature-to-module map);
`ai.view` and `ai.manage` in `config/platform_roles.php`.

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

The existing `Ai/AiOperationsTest` (the cost report and its grant) still passes
unchanged. `SaasPanelTest` counts the two new `/saas` routes.

## Endpoints (HQ panel)

| Method | Path | Name | Ability |
| --- | --- | --- | --- |
| GET | `/saas/ai` | `saas.ai` | `ai.view` |
| PUT | `/saas/ai/switch` | `saas.ai.switch` | `ai.manage` (throttled 30 a minute) |

No API or OpenAPI change.

## Not in this step

- **Editing caps from HQ.** Caps have two homes: the platform ceilings in
  `config/ai.php` (trusted server configuration, changed by deploy) and each
  school's own lower limits in `ai_school_limits`, which the school's managers
  set at `/ai-settings` with revisions and receipts and which can only lower
  the ceiling. An HQ editor for the school's row would overwrite a school's
  own decision, so it is left for the owner to decide.
- **Recording evaluation results.** `ai:eval` prints its score and stores
  nothing; storing results (and showing them here) needs its own table and a
  decision on what counts as qualification evidence.
- **Recording qualification from HQ.** Qualification stays a config change.
- **Rands while metering is in USD.** No exchange rate is applied (see
  Currency).
- **Merging the dated cost report into HQ** (see the old page).
- A monthly cap: the gateway's caps are daily; the page shows the month's
  total without a monthly limit.
