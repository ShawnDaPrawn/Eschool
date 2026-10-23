# Handles and guardian review

Handles are global human identities. They work beside email, GR numbers and
student numbers; existing login names take precedence. V1 user output keeps
its shape, while context and the new v2 handle endpoint add the chosen handle.

The accepted format is 3–20 ASCII lower-case letters, digits, dots or
underscores, starting with a letter and ending with a letter or digit.
Repeated punctuation, reserved service/role names and school slugs are
refused. Learner handles cannot contain their surname (punctuation ignored),
student number or common full date-of-birth formats. These rules are enforced
by the server, not entrusted to app validation.

Under-18 learners, and learners without a date of birth in their
record, propose a handle. It remains inactive until a currently authorised,
verified and active guardian approves it at the learner's school. Proposals
expire after seven days, can be rejected, and are superseded by a newer
proposal from the same human, even across schools. A pending name is reserved
until its proposal expires. Guardian rights and expiry are checked again at
the decision; replay is refused. Adults choose directly.

Allocation locks the human and the globally unique claim. A change is allowed
once per term; overlapping terms across the human's active schools count, so
switching or joining schools cannot bypass the limit. Without a configured
term the current calendar quarter is used. The old handle remains reserved
for 90 days. A rejected proposal for a previous handle never shortens that
original hold. Changes have an explicit history and audit entry.
Learners with handle-request history must be archived; the legacy permanent
cleanup refuses before removing academic data. Handle approval never grants
the separate account-access consent.

Endpoints:

- `GET/PUT /api/v2/me/handle`: your own current handle, proposal and next change
  date; request bodies cannot target another user.
- `GET /api/v2/guardian/handle-proposals`: pending requests for your authorised
  learners at the selected school, after email verification.
- `POST /api/v2/guardian/handle-proposals/{proposal}`: approve or reject, with
  current relationship checks. Other schools cannot see or decide the request.

The shared app screen supports choosing, waiting, guardian decisions and
retrying a refused name without losing the typed value. Existing servers hide
its settings entry. New labels cover the eight existing app languages. School
context changes are queued through the existing outbox; a dedicated mandatory
guardian notification is connected when the notification registry is built.

Staging email, real-device and first-time UX evidence remain separate from
automated verification. No production rollout is implied.
