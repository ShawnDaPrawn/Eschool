# Academic report operations

Migrate, run the ordinary queue worker and per-minute outbox dispatcher, and keep
APP_KEY and the academic_private disk in the tested private backup/restore set.
The public storage link must never expose that disk. Preserve old keys during a
key rotation so retained evidence remains decryptable. Configure APP_URL/trusted
proxy settings to the actual API origin so signed download URLs validate.

Configure a school report design and generic comment banks. Select the year,
homeroom, term (or annual period), learners and official/provisional mode; preview
current canonical evidence before preparing. Missing/incomplete learners can be
left for another batch. Provisional running reports are visibly labelled, never
silently final. Earlier-day attendance corrections remain in the register service.
Report attendance summarises captured homeroom states, with integer half-day units;
uncaptured dates are not absences. Canonical state takes precedence over v1 rows.

Allocated teachers comment only in their own current scopes. Independent draft
comments can progress together; competing edits to one scope must be compared.
School managers/current report leaders need a reason for administrative comment
corrections. They can handle report history after a year rollover; an expired
teacher allocation grants no historical write permission. Any accepted comment
or explicit evidence refresh clears signatures. Do not automatically rebase a
conflict or reuse an old approval fingerprint.

A current grade head signs, then a current principal. The outbox queues one
bounded PDF job per card. Preview the generated PDFs, then release the selected
batch and notify families in one action. Release checks fresh source evidence and
all selected artifacts. The retry action requeues approved generation without
duplicating notices. A changed source requires explicit refresh and new sign-off.
No account activation or fee status enters these decisions.

Published reports are immutable. Issue a new version for a correction. If a
released artifact is missing/corrupt, restore the verified private backup with its
original metadata; never rewrite published payloads or replace an old release
with today's marks. Preserve retained documents under the school's retention rule.
Migration rollback refuses to discard report evidence.

Family links expire in ten minutes and require the same authenticated requester
and school. Reload the list or request fresh card access for a new link. Retained history
is paged; keep the earlier-page control available. A shared link does not authorize
another guardian/account; ended guardianship revokes even an unexpired link.
Clients should keep authenticated PDF bytes in route memory and discard them on
school/child/account replacement. Notification delivery is queued: provider
receipts and real parent delivery must be verified operationally on staging.
The local synthetic PDF and fake transports do not establish pilot acceptance.

Current grade heads may correct earlier-year registers with a reason. A teacher
whose allocation has expired cannot use a historical report or register to regain
access. Register corrections retain the original attendance date and audit trail.

The panel and both apps expose Report cards. The panel deliberately permits
explicit historical report operations while the legacy header shows a read-only
year; canonical current authority, signatures and revisions remain mandatory.
Panel writes and encrypted retry receipts commit together. API report retry keys
are school-bound and report response bodies are encrypted in the receipt store.
PDF clients validate the configured origin/path/signed query and refuse redirects;
resume and explicit Save a copy refresh current access. Receipts require the same
application encryption-key backup/rotation handling as report evidence.
