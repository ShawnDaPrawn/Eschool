# Consented school transfers

School Admins with academic-management permission and current appointed
principals prepare and release a frozen package. A named, verified guardian with
current approval rights must explicitly consent to its exact fingerprint and
named destination. Source and receiving authority, password readiness, module
availability and platform/impersonation exclusion are checked freshly.

The package carries released reports/marks, dated attendance, and selected
current documents visible to both the exporter and consenting guardian. Clinical
notes, support, risk and discipline records are excluded. Bundle contents are
signed using the existing school Ed25519 signing service, encrypted in private
school storage and bounded to 50 MiB. No arbitrary uploaded archive is imported.

Receiving acceptance copies the verified frozen archive into the receiving
school's private namespace. A single database transaction closes the source
enrolment and future teaching memberships and creates the receiving enrolment
on the consented start date. Source and receiving school rows lock in stable
order. Received marks remain labelled historical source records; they are never
inserted as locally assessed marks. Only the consenting guardian is linked to
the destination. Destination learner approval and parent policy acceptance remain
separate; a transfer never silently activates a learner account.

Exact operation UUIDs replay references after fresh authority checks; private
bodies are not cached in HTTP receipts. File staging is bounded and expires if
not adopted by a committed receipt. A guardian may withdraw before acceptance;
completed institutional records remain retained. An external-school departure closes the current source enrolment and provides
a signed archive for manual handoff. Exports from an already departed or older
enrolment episode are explicitly export-only and cannot cancel a newer place. Development uses synthetic records only.


## Private workflow

The staff panel has sending, receiving and retained-history views, named paged
choices, frozen package review, release, cancellation and placement. It requires
the browser's original account and school on every write and returns escaped
validation drafts directly with no session old-input flash. Historical-year
refusals authorize before reflecting a draft. API writes are body-only and carry
an operation UUID; reads and refusals forbid caching.

API v2 exposes eleven additive transfer operations and typed historical record
schemas. Guarded archive reads verify the school's signature and private path,
then recheck authority and availability after reading. Receiving copies remain
verifiable and readable under receiving-school authority after the temporary
source copy expires. Retained records prevent destructive migration rollback.
A generic guardian review notice contains no learner name, destination or marks;
fanout and transport recheck the named guardian and current request revision.

Native parent consent and actual browser interaction qualification are implemented. No production or provider activation follows from these
implementation checks.


## Parent review and explicit file export

The Parent home card and guarded deep route operate on the current guardian
account and school, including when no approved child is selected. The reader
shows the named destination, dates, report/document selection, released source
marks and attendance. Consent binds the immutable package fingerprint and current
version. Withdrawal is explicitly confirmed before completion. Unknown outcomes
retry the same serialized UUID/revision intent; a known conflict reloads current
records and clears prior approval.

Private bodies and drafts stay in memory, are erased on suspension, and reload
under fresh authority after resume or context refresh. Old repository generations
cannot reactivate under the same account. File exports carry the original
account/school, recheck after download and open a device save dialog only after
an explicit tap. A denied or late export cannot save a private file. Large source
histories expand in bounded batches. No transfer body enters an offline cache.
The staff external-school select has an explicit blank destination; it cannot
silently route an external request to the first NavigateEd school.
