# Staff invitations

Action Plan step 1.11. A new school's first Super Admin and its staff choose
their own password from an emailed link. Before this, the platform showed a
one-time password for someone to pass on, and staff added in the panel got
their date of birth as a password.

## Model

`staff_invitations` (school-owned): `user_id`, `created_by`, `purpose`
(`school_admin` or `staff`), `token_hash` (SHA-256 of the 64-character secret),
`encrypted_token` (to re-send the same link), `email_hash` (the address it was
sent to), `expires_at` (seven days, `identity.invitation_days`), `used_at`,
`accepted_at`.

## Rules

- **One live link per person.** Issuing a new invitation closes every unused
  one, so only the latest link works.
- **Bound.** A link opens only while it is unused and unexpired, the school is
  active, the person's account is active, their email still matches the address
  it was sent to and they still have a current staff (non-learner, non-parent)
  membership of that school. Otherwise it is "not found", and a queued email is
  suppressed at delivery (`StaffInvitationMail::mayDeliver`).
- **Accepting** sets the chosen password (common password policy; not the
  current or a date-of-birth password), verifies the email address, clears
  `password_change_required`, revokes every app token and browser session, marks
  the link used and audits it. Anyone signed in on that browser is signed out,
  and the person signs in afresh. The link works even while a signed-in default
  password still has to be changed (`RequirePasswordChange` lets it through).
- **Secrets stay secret.** The token is never in the audit log, the session or
  a model's array form; only its hash is searchable. Both routes are rate
  limited (`throttle:30,1` to view, `throttle:password` to accept).

## Who can send one

- **Platform staff**, when they set up a school: the first Super Admin gets a
  `school_admin` invitation. Their starting password is random and known to
  nobody. The link is shown once on the result page as a fallback while mail is
  not live. The school page offers "Send the invitation again" until it is
  accepted, without naming the person (the platform area lists no people).
- **School staff**, from a person's profile page ("Send sign-in invitation"):
  `teacher-edit` for a teacher, `staff-edit` for other staff, and only a Super
  Admin for a Super Admin, so a changed email address can never hand that
  account to someone else. Nobody invites themselves, and learners and parents
  are never sent a staff invitation (they have guardian and learner
  invitations).

## Routes

| Method | Path | |
| --- | --- | --- |
| GET, POST | `/invite/staff/{token}` | the page and the password form; no sign-in needed |
| POST | `/people-profiles/{person}/invitation` | school panel send, `throttle:20,1` |
| POST | `/saas/schools/{id}/administrator-invitation` | platform re-send |

## Acceptance tests

`Platform/StaffInvitationsTest`: a teacher chooses a password from the emailed
link, old app tokens and sessions end, the link then opens nothing; the link
also works while signed in with a default password that must be changed; a newer
link cancels the older one; a changed email, expiry or ended membership opens
nothing and suppresses the queued email; a teacher cannot send invitations,
parents, learners, oneself and other schools' people are refused, a staff editor
cannot invite a Super Admin; the profile page offers the button only to someone
who may send it; the secret is not in the audit log. `Platform/SaasPanelTest`:
the new school's administrator gets an invitation (not a password), chooses a
password and opens Home; re-sending works until acceptance, then does nothing.
Browser: `e2e/tests/saas-panel.spec.ts` follows the link in a second browser at
both widths.

## People who already have an account

Someone whose address already has an account is not sent this link: a school
invites them to join with that account ([existing-account-invitations.md](existing-account-invitations.md),
Action Plan 1.3), and their password is never reset. A person who also belongs to
another school is never sent a set-your-password link from a school either: the
"Send sign-in invitation" button answers as usual and sends nothing
(`staff_invitation_withheld`), and the staff import skips them.

## Not yet

- Reminders before expiry (guardian invitations have them).
- Replacing the date-of-birth password in the legacy staff and teacher create
  forms with an automatic invitation; until then, send one from the profile.
