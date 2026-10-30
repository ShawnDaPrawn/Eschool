# One-click unsubscribe from optional notice emails

Action Plan 2.4, the unsubscribe part. Owner role: Backend (notifications).

Email providers ask bulk senders to support one-click unsubscribe (RFC 8058): a `List-Unsubscribe` header with an HTTPS link, and `List-Unsubscribe-Post: List-Unsubscribe=One-Click`, so the provider can unsubscribe the reader with one POST. NavigateEd already lets people switch notice types off in their notification settings ([notification rules](notification-rules.md)); this makes the same choice reachable from the email itself, for the notice types a person is allowed to switch off.

## Rules

- **Which emails.** A school notice email (`NotificationMail`) carries the headers and a "Stop these emails" footer link only when its type is offered to the recipient at that school and its email channel is not mandatory there. Account safety notices, urgent school notices, absence alerts, and any type the school has made mandatory never carry it. Invitation and password emails are not notices and never carry it.
- **The link.** It is a signed link for one person, one school and one notice type: `/email/unsubscribe/{user}/{school}/{type}`, valid for 90 days (`EmailUnsubscribe::DAYS`). Changing any part breaks the signature (403). The link names a user id, never the email address.
- **Opening it (GET)** shows the school, the notice's name and what will happen, and asks to confirm. It changes nothing, so a mail scanner that follows links cannot unsubscribe anyone.
- **One-click (POST)** switches that type's email off for the person at once (the plan allows up to two days), exactly as their own notification setting would (`NotificationRules::choose`). Push and in-app notices of the type are unchanged, and the person can switch the email back on in the app. The POST takes no session or CSRF token: mail providers send it, and the signature is the authority.
- **Required notices.** A correctly signed link for a type that is mandatory, not offered, or at a school the person is not at switches nothing off, and says the school needs to send it.
- **Repeat.** Using the link again says the emails are already off.
- **Logs.** An unsubscribe logs `email_unsubscribed` with the user, school and type ids, never the address. The preference change is audited like any other preference change.

## Endpoints

| Method | Path | Name |
| --- | --- | --- |
| GET | `/email/unsubscribe/{user}/{school}/{type}` | `notifications.unsubscribe` |
| POST | `/email/unsubscribe/{user}/{school}/{type}` | `notifications.unsubscribe.store` |

Both run behind `signed` and `throttle:30,1`, and answer with private, no-store headers. The POST is exempt from CSRF verification (`bootstrap/app.php`).

## Acceptance tests (`Platform/EmailUnsubscribeTest`)

| Given | When | Then |
| --- | --- | --- |
| Farid has School notices by email on | a notice email is sent through the queue job | it carries `List-Unsubscribe` with the signed link, `List-Unsubscribe-Post: List-Unsubscribe=One-Click` and the footer link; opening the link changes nothing; a one-click POST switches only the email off, once |
| An absence alert (mandatory for parents) | sent, and a hand-made signed link for it is used | no header or footer link; the link switches nothing off |
| A signed link | its user, school or type is changed, the signature is removed, or it has expired | 403, nothing changed |
| A signed link for a school the person is not at, or an unknown person | used | nothing changed; unknown person 404 |

## Not in this step

- The delivery report half of 2.4 (per-message counts and per-recipient status for the sender in the communication centre).
- A "manage all my email notices" page reachable without signing in.
- Branded email and the full-content template (2.1).
