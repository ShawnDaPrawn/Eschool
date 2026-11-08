# Child mode in the parent app

Action Plan 4.2, "Child mode in the parent app". This step builds the server side: the child's PIN, a short-lived child token issued from the guardian's parent-app sign-in, what the child may open, revocation, the API, and the audit trail. The app screens are a later step (see "Not in this step").

## Why

Junior learners (Grade 3 to 7 by default) mostly use a parent's phone. Child mode lets a guardian hand the phone to the child with a small, safe set of things to do, locked by a PIN, without giving the child the parent's account or their own sign-in.

## User stories

- As an approving guardian, I set a PIN for my child, and change it when I need to.
- As an approving guardian, I enter the PIN and hand my phone to my child in child mode.
- As a junior learner in child mode, I see my day, the reading library, practice from Grade 3 and the tutor from Grade 4, and nothing else.
- As a guardian, I leave child mode by entering the PIN, or end it from another device without the PIN.
- As a school, if a guardian is removed, loses the approval right, or the child's approval is withdrawn, child mode ends at once.

## Rules

1. **Who.** Only an approving guardian may set the PIN or start child mode: an active guardian link to the child with the approval right (`guardian_links.can_approve`), an active account and a verified email. The child must hold a current guardian approval (`ApprovalService::approved`) and not have left the school. Another family's child, or another school's, answers 404, so nothing about the child is revealed. A guardian of the child without the approval right sees why child mode is closed (`not_approving`) and is refused (403) when setting the PIN or starting.
2. **Stage.** Child mode is for the junior stage (Action Plan 4.1, `SchoolStages`). Early years has no child mode on personal devices (`early_years`); high school learners use their own sign-in (`high_school`). A learner whose homeroom has no grade gets none (`grade_unknown`), because the early-years rule could not be applied. A school that moves a grade between stages moves child mode with it.
3. **What a child may open.** `my_day` and `library` from the junior stage, `practice` from Grade 3, `tutor` from Grade 4. Each only while its module (`daily_practice`, `tutor`, `reading`) is switched on at the school and its configured stages include junior. Never chat, messages, other learners' marks, rankings or anything else. It is worked out fresh on every request.
4. **The PIN.** 4 to 6 digits, typed twice, not one digit repeated or a straight run (1234, 9876). Stored only as a hash (`child_mode_pins.pin_hash`), one per child at a school, shared by the child's approving guardians. Setting a new PIN changes its version and ends every child session for that child.
5. **Throttling and lockout.** Wrong PINs are counted per child under a row lock, whichever guardian or device enters them, and saved before the refusal. After 5 wrong PINs (`identity.child_mode_pin_attempts`) the PIN locks for 15 minutes (`identity.child_mode_pin_lock_minutes`): every try answers 429 with `Retry-After`, even the right PIN. A guardian setting a new PIN clears the lock. The PIN routes are also limited to 10 a minute per account (`throttle:child-mode-pin`).
6. **The child token.** A Sanctum token named `child-mode-app` on the guardian's account, with abilities `child-mode:school:<id>` and `child-mode:learner:<id>`, expiring after 30 minutes (`CHILD_MODE_MINUTES`, 5 to 120). Issued only from a parent-app sign-in (a `parent-app` token, not a web portal or another app's token). One child session per parent-app sign-in: starting another ends the first.
7. **The token opens nothing else.** Sanctum asks `ChildModeAccess::allowedHere` about every token it finds; a child token authenticates only on a route behind `ChildModeContext`. Everywhere else it is no token at all: routes that need a sign-in answer 401, public routes answer as to a visitor. `ChildTokenRouteListTest` proves this over every route that takes app tokens.
8. **Every child request re-checks.** `ChildModeContext` runs the request in the token's school (an `X-School-Id` naming another school is refused with 403), and `ChildModeSessions::verify` re-reads the session row, the parent-app sign-in it came from, the PIN version, and rules 1 and 2. If anything changed, the session ends and its token is deleted (401).
9. **Revocation.** The child token is deleted at once, and the session row records why, when:
   - the guardian is removed (`GuardianLinks::end`, or a legacy parent field cleared): `guardian_removed`;
   - the guardian loses the approval right (`GuardianLinks::update`): `approval_right_removed`;
   - a guardian withdraws the child's approval (`ApprovalService::decide`): `approval_withdrawn`;
   - the PIN changes: `pin_changed`;
   - the guardian signs out of the device that started it (`AppTokens::signOut`), or the parent app is signed out for the account (`AppTokens::revokeApp`): `parent_signed_out`;
   - the guardian signs out everywhere (`AppTokens::revokeAll`: password change or reset, invitation acceptance, account removal): `signed_out_everywhere`;
   - the guardian ends it from the parent app: `ended_by_guardian`; the child leaves with the PIN: `ended_with_pin`; another session replaces it: `replaced`.
   A session already past its expiry is recorded as `expired` at its expiry time.
10. **Audit without content.** `child_mode_sessions` keeps ids, times and the end reason. The audit log records `child_mode.pin_set`, `child_mode.pin_changed`, `child_mode.pin_locked`, `child_mode.session_started` and `child_mode.session_ended` with ids, times and the reason only: never the PIN, its hash, the token, or anything the child opened or typed.
11. **Never across schools.** Both tables are school-owned. A guardian with children at two schools has a separate PIN and separate child sessions at each, and a child token works only in its own school.

## API

All under `/api/v2/child-mode`, private and never cached. Guardian routes: `auth:sanctum`, `school.context`, `role:Parent`, `parent.policy`.

| Method and path | Who | What |
| --- | --- | --- |
| `GET children/{child}` | Guardian | Whether child mode is open, why not, PIN set or locked, what the child may open, the session length. |
| `PUT children/{child}/pin` | Approving guardian | Set or change the PIN (`pin`, `pin_confirmation`). |
| `POST children/{child}/sessions` | Approving guardian | Start child mode with the PIN; returns the child token and the session. |
| `POST children/{child}/sessions/end` | Guardian | End their child sessions for this child on every device, without the PIN. |
| `GET session` | Child token | The child's first name, school, what they may open now, and the expiry. |
| `POST session/end` | Child token | Leave child mode with the PIN. |

## Data

- `child_mode_pins`: `school_id`, `learner_id` (unique per school), `pin_hash`, `version`, `set_by`, `failed_attempts`, `locked_until`.
- `child_mode_sessions`: `school_id`, `learner_id`, `guardian_user_id`, `token_id` (unique), `issued_from_token_id`, `pin_version`, `started_at`, `expires_at`, `ended_at`, `end_reason`, `ended_by`. The rollback refuses while any rows exist.

## Tests

`tests/Feature/ChildMode/ChildModeTest.php`: the happy path, grade and module rules, early years, high school and ungraded homerooms, the lockout, weak PINs, expiry, one session per sign-in, each revocation, a pruned parent sign-in, wrong guardian, another family, a non-approving guardian, another school, wrong role and wrong token, and a child token on the guardian routes. `tests/Feature/ChildMode/ChildTokenRouteListTest.php`: the route-list proof. `ApiRouteTenancyTest` and `OpenApiContractTest` cover the new routes.

## Not in this step

- The app screens: the PIN-locked child shell in the parent app (`e-school`), built from `eschool_core` widgets, with large buttons, pictures and spoken instructions; the PIN set, change and entry screens; leaving child mode; the guardian's "child mode" entry on each child.
- The content endpoints behind the four abilities for a child token (my day, practice, tutor, library). Today the child token reaches only its own session routes; each of those features accepts the child token in its own later step, gated by `opens`.
- The tutor's paid entitlement for a child session (the tutor checks its own entitlement when it accepts child tokens).
- The Dart client (`packages/eschool_api`) regeneration for the new operations.
- A "sign out of every device" button in the parent app (the server already ends child mode whenever the account is signed out everywhere).
- Retention of `child_mode_sessions` rows.
