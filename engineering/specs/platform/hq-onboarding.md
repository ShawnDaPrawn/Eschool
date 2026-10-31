# HQ onboarding checklist

Action Plan SA.6. Owner role: Backend and Admin UX (HQ).

## User stories

- As Navigate Ed operations, I see for each school which onboarding steps are done and what blocks the rest, from the school's live data, so nobody keeps a separate spreadsheet.
- As a support person, I can read the checklist. Only operations, finance and owners (`accounts.manage`) can record training.
- As a school, my learners', parents' and staff's details never appear in HQ: the checklist holds counts only.

## Steps

Each step is *done*, *in progress*, *not started* or *not yet available*, with a one-line detail and the next action. *Not yet available* steps do not count towards "N of M steps done".

| Step | Done when | Detail |
| --- | --- | --- |
| Contract and operator agreement | a contract covering today is recorded (operator agreement signed on recording, #359) | none recorded / not current |
| First administrator signed in | the school administrator invitation (#358) was accepted | waiting (sent, not expired) or not waiting |
| Email from the school's own domain | `school_sending_domains` ([sending domains](sending-domains.md)) | verified (done); connected and waiting (started); none (to do); "later" while `MAIL_SENDING_DOMAINS` is off |
| Homerooms set up | every homeroom's class has a grade | homerooms and how many have a grade |
| Learners imported with LURITS numbers | every learner has a LURITS number (#354) | learners and how many have one |
| Guardians linked and approving | at least 80% of learners hold a current guardian approval (`ApprovalService::approvedLearnerCount`, the same rule as access) | learners with an approving guardian linked, and the approved share |
| Staff invited and signed in | everyone with a staff role accepted an invitation | staff, invited, signed in |
| Modules switched on | at least one module is on | modules on |
| Training sessions held | at least one session is recorded | sessions recorded |

Training sessions are recorded on the school's HQ timeline (`school_account_events` kind `training`), with a date (not in the future), a topic and, optionally, the number of people. The audit action is `platform_school_training_recorded`.

## Acceptance tests (`Platform/HqOnboardingTest`)

| Given | When | Then |
| --- | --- | --- |
| The demo school as built | checklist | contract not started; domain later; homerooms "2, 0 with a grade" in progress; learners "3, 0 with a LURITS number" in progress; training not started |
| A current contract, graded classes, an accepted administrator invitation, every learner approved by a verified guardian | checklist | contract, administrator, homerooms and guardians done (100%); School B's checklist unchanged |
| Support | open the page; record training | counts shown, no learner, parent, staff or School B name; no training form; recording refused (403) |
| Operations | record a session yesterday for 12 people; record one tomorrow | on School A's timeline only, training done; the future date refused; an unknown school 404 |
| A school Super Admin (not platform staff) | open the page | 403 |

## Endpoints (HQ panel)

| Method and path | Ability |
| --- | --- |
| `GET /saas/schools/{school}/onboarding` | `schools.view` |
| `POST /saas/schools/{school}/training` | `accounts.manage`, throttled 20 a minute |

## Security notes

- Every count is read inside `CurrentSchool::run($school)`, so each school's data stays its own.
- No query returns a person: names, emails and numbers never leave the school.
