# Weekly parent learning updates

Parents read a short Monday update for the selected child from released marks,
recorded homeroom attendance, daily-practice completion and upcoming school
calendar events. A single home instruction opens the selected child’s released
results, attendance, calendar or published learning material. A practice action
uses the existing authorized material reader; it does not grant Parents the
Student practice API.

The `digest` school module defaults off. Managers create immutable language
versions and explicitly approve or withdraw their wording. The English starting
text is a draft, never an implicit approval or a translation into another
language. A preferred language without approval waits; other families continue.
Parents change their preference for future weeks. The twelve existing offered
language codes are supported, including right-to-left rendering.

`digest:weekly` runs hourly across active schools and composes once per
child/Parent/week after 07:00 school-local Monday. Exact evidence is retained in
encrypted receipts for at most 26 weeks; daily retention runs even when the
module is off. No clinical notes, staff risk labels or generated prose enter a
weekly update. Generic `digest.weekly` wording and an opaque receipt identifier
travel through Notifier; the summary remains behind current family authority.

Every read and delivery refreshes account, school, module, active learner
membership/enrolment/approval and the actual guardian relationship. Platform
staff and impersonation cannot use the domain. Prepared queries reapply their
fresh source guard. The Parent screen erases private text on background,
account/child replacement, expiry and refusal. It keeps no disk copy. Mutations
retain bounded exact operation IDs through uncertain transport outcomes; no
write is automatically replayed. Manager forms bind the current account/school,
use private validation responses and reject historical-year writes.

Seven additive v2 operations and 24 schemas live in
`engineering/contracts/weekly-digests.openapi.yaml`; the canonical contract and
pinned generated Dart SDK are updated together. Manager panel:
`/weekly-digest-templates`. Parent home: **Weekly learning update**; notifications
open the same selected-child reader.
