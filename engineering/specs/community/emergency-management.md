# Emergency management

Status: built (server, panel and API v2). App screens come later.

Emergency management lets a school send critical alerts and lockdowns to its
staff and families, keep a drill log, sign visitors in at the gate, take a roll
call by class and release children to the right adults during an emergency.
It is built on the school's existing records: ID cards and gate readers
([cards.md](cards.md)), guardians and their verification, and early collection
requests ([family-visits.md](family-visits.md)). No outside safety vendor is
used.

The `emergency` module (Settings > Modules, product module "Access and
admissions") is off by default. Panel: **Emergency management**
(`/emergency`). API: `/api/v2/emergency/*` (tag `Emergency` in `openapi.yaml`).

## Who may do what

Three permissions, in the role editor under "Emergency alerts, drills, visitors
and reunification":

| Permission | Level or ability | Allows |
| --- | --- | --- |
| `emergency-alert-send` | ability "Send emergency alerts and lockdowns" | prepare, confirm, cancel and clear alerts |
| `emergency-respond` | contribute | roll call for every class, visitor sign-in and sign-out, releasing children |
| `emergency-manage` | manage | the drill log and how long visitor details are kept |

Super Admin has all three. Role templates: principal and deputy principal get
manage and the alert ability; school office and reception get contribute. An
ordinary teacher has none of them: a teacher can only take the roll call for
the classes they are class teacher of this year. Guardians see only their own
children's status. Learners are refused everything and never receive alerts.

Every check reloads the person and their roles at the current school (an old
role or another school's membership never counts), refuses platform staff and
impersonation, and refuses while the module is off.

## Critical alerts and lockdowns

- **Kinds:** `lockdown`, `evacuation`, `shelter` (shelter in place) and
  `critical` (a general emergency alert, which needs its own message). Each
  kind has a plain default title and message the sender can change.
- **Audience:** `everyone` (every member of staff and every guardian) or
  `staff`. Recipients are worked out when the alert is sent: active
  memberships at this school, any role but Student; never anyone who is a
  learner here.
- **Two-step confirm.** Step one (`prepare`) saves the alert as `pending` and
  sends nothing. Step two (`confirm`) needs the confirmation word typed exactly
  (LOCKDOWN, EVACUATE, SHELTER, ALERT, or DRILL for a drill), within 10
  minutes, by someone with the alert ability (the same person or another).
  Only one alert can be active at a time. A pending alert can be cancelled.
- **All-clear.** Goes to the same audience, with a default or typed message.
- **Notification type `safety.critical`** (config/notification_types.php):
  push, email and in-app are mandatory and locked for guardians and staff, it
  is `quiet_hours_exempt` (never held for a person's quiet hours, and
  time-sensitive on iOS), and it belongs to the always-on communication
  module, so a delivery is never dropped because a module setting changed
  after sending.
- **SMS and WhatsApp.** `safety.critical` is in config/urgent_messaging.php
  with `every_enabled_channel: true`: it goes by each text channel the school
  switched on (Settings > SMS and WhatsApp) without being ticked per type, and
  with `content: school_text` the text always carries the school's title and
  message (an emergency alert is never about one child). The settings page
  says so and does not offer it as a choice.
- **Idempotent.** Every write takes an `operation_id`. The same id and body
  returns the first receipt (after authority is checked again); a different
  body under the same id is refused. A second confirm or all-clear finds the
  alert already sent and is refused (409). Recipients are claimed per notice,
  person and channel (`notification_dispatches`), so a retried fanout never
  tells anyone twice.
- **Audit.** `emergency_events` (append only) records each step with its
  actor: `alert.prepared`, `alert.confirmed` (with who prepared it and how many
  were told), `alert.all_clear`, `alert.cancelled`. The platform audit log also
  gets `emergency.alert_sent` / `emergency.drill_alert_sent` and
  `emergency.all_clear_sent` with who prepared, confirmed and cleared it.

## Drills

- **Drill log** (`emergency_drills`): type (fire, lockdown, evacuation,
  shelter, other), planned time, actual start and end, notes, who recorded it.
  Managers plan, record and cancel drills.
- **Drill alerts.** An alert prepared with `drill: true` is labelled whatever
  the sender typed: the title starts "DRILL:", the message starts "This is a
  drill. There is no emergency.", the push payload has `drill: "1"`, and texts
  carry the same words. The confirmation word is DRILL. A drill alert can be
  tied to a planned drill: confirming it fills in the actual start, the
  all-clear fills in the end. A real alert can never be tied to the drill log.
- Roll call is practised in a drill; children are never released in one.

## Visitor sign-in at the gate

- **By card** where the school uses ID cards: gate staff sign a visitor in
  with a guardian card scanned at a gate reader in the last two minutes (an
  accepted QR or SUN scan, from an active gate reader of this school). The
  name comes from the card's account. A scan signs in one visit only.
- **By hand** otherwise (or when the cards module is off): name, reason, and
  optionally phone, organisation and who they are visiting.
- **On site:** everyone signed in today (school day) and not signed out. The
  roll call shows the count to staff who see every class.
- **Retention rule.** Name, phone, organisation, reason, host and the linked
  account are encrypted at rest and erased a number of days after sign-in
  (default 30, the school sets 1 to 90). `emergency:purge-visitors` runs daily
  at 04:15 for every school, whether or not the module is still on. The times
  and the sign-in method stay, for counts.

## Roll call and reunification

- **Roll call per class** for an alert that was sent in the last 24 hours
  (also after its all-clear): each current learner enrolled today is
  accounted, not yet accounted (no mark) or released. Class teachers see and
  mark only their own classes; responders see every class.
- **Release** (responders, not in a drill) needs one of:
  - a **guardian** of the child (`GuardianService::guardianUserIds`, with an
    active Parent membership here), checked by their guardian card scanned at
    a gate reader in the last two minutes (the card must be theirs) or by staff
    checking an identity document; or
  - the **named collector** of an early collection request for that child,
    approved by staff and valid today, whose requesting guardian and approving
    staff member still hold that authority (`GatePasses::validSource`), with
    the name on their identity document matching the request.
  Anyone else is refused (403, "Do not release the child") and nothing is
  written. The person releasing must tick that they checked the identity.
- **Every release is logged** (`emergency_releases`, never changed or
  deleted; collector name and notes encrypted), once per child per alert, with
  a `child.released` event and an `emergency.child_released` audit row.
- **Guardians** (`GET /api/v2/emergency/status`) see the current alert sent to
  families and, for each of their own children only, the status, when they
  were released and to whom. Asking for another child is 404.

## Keeping schools apart

All eight tables are required school-owned tables (school_id NOT NULL,
indexed, foreign key, `BelongsToSchool`): `emergency_settings`,
`emergency_drills`, `emergency_alerts`, `emergency_roll_calls`,
`emergency_releases`, `emergency_visitors`, `emergency_operations` and
`emergency_events`. Learner, alert, drill, gate pass and card scan references
use composite school foreign keys. Recipients, roll calls, scans and passes are
read through the school scope, so a School B alert is never sent to or seen by
School A people, and an id from another school is not found.

## API

| Operation | Who |
| --- | --- |
| `GET /emergency/status` | staff and guardians |
| `POST /emergency/alerts` (prepare) | alert ability |
| `POST /emergency/alerts/{alert}/confirm` | alert ability |
| `POST /emergency/alerts/{alert}/all-clear` | alert ability |
| `GET /emergency/alerts/{alert}/roll-call` | class teachers (own classes), responders |
| `POST /emergency/alerts/{alert}/roll-call` | class teachers (own classes), responders |
| `POST /emergency/alerts/{alert}/releases` | responders |

Cancelling a pending alert, visitors and the drill log are panel-only in this
step.

## Seams

- **SMS and WhatsApp follow-ups (#457, not merged).** It adds number
  verification, each person's own channel choice and staff texts. This step
  touches `UrgentMessaging` only in `enabledFor` (the `every_enabled_channel`
  flag), `content` (`school_text`) and `choosableTypes`. Once #457 is merged,
  `safety.critical` should be added to its staff types so staff are texted
  too, and its verification and choice rules apply unchanged; until then staff
  get push, email and in-app only, as `urgent_messaging.portals` is guardians.
- **Device-bound gate flow.** Card checks use a recent server-written scan
  from a gate reader, referenced by gate staff signed in to the panel. A gate
  reader could later sign visitors in and check guardians itself, through the
  delegated device credential (`card-device/*`), as early collection does.

## Tests

`tests/Feature/Emergency/EmergencyManagementTest.php` (every v2 response is
checked against `openapi.yaml`):

- happy path: prepare, wrong word refused, confirm, delivery to staff and
  guardians and never learners, audit of who triggered it, one active alert,
  all-clear to the same people, staff-only audience;
- wrong role: a teacher, a guardian and a learner cannot prepare, confirm,
  cancel or clear (service, API and panel); module off is 404;
- wrong school: a School B alert reaches no School A person, and School A
  cannot clear it, read its roll call or see it;
- wrong owner: a guardian sees only their own children and gets 404 for
  another child; a learner is refused;
- unauthorised release refused: another child's guardian, a teacher, a School
  B guardian, someone else's card, a missing, pending or other child's pass, a
  name that does not match; then a valid collector and a guardian card work,
  once each; nobody is released in a drill;
- quiet hours: a critical alert push is sent at once during quiet hours while
  an ordinary notice is held;
- drill labelling: title, message, text, payload and status say DRILL; the
  drill log gets its times; a real alert cannot use the log;
- idempotent broadcast: a repeated confirm returns the first receipt, sends one
  notice, and a re-run fanout tells nobody twice; the API all-clear likewise;
- roll call per class and class teacher limits; visitor sign-in by card and by
  hand, encryption, on-site list, and erasure after the retention period;
- the panel's two-step send.

## Not in this step

- App screens (Flutter) for alerts, roll call, release and the guardian view,
  and the regenerated Dart client.
- Gate reader (device credential) endpoints for visitor sign-in and guardian
  checks; panel staff use a scan reference.
- Notifying guardians when their child is released.
- Visitor badges or printed passes, and a visitor watch list.
- A third-party safety vendor (by decision).
