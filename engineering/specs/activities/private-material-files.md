# Private binary activity materials

Binary PDF/JPEG/PNG attachments belong to one authored material in one school
and group. Current group staff may attach only to their own material unless they
hold the explicit group-management permission. Uploads require the current year,
an active group, a current allocation, the expected material revision and writable
groups. Students and current guardians read only published materials through the
same fresh membership/enrolment/approval authority as the activity reader.

The server scans the original, strips image metadata using SafeImages, scans the
result and stores ciphertext on a private disk. An unavailable scanner refuses
the upload. Object names are generated and every read checks the material's
current publication, group, file identity/path and content hash before returning
private bytes. Withdrawn or replaced membership never grants access through an
old file ID. There are at most ten active attachments per material, each at most
5 MiB (images use SafeImages' stricter 4 MiB source limit).

Operations use the existing school-locked activity receipts; retries cannot add a
second attachment. Attachment changes advance the material revision. Removal is
allowed as a revocation in read-only mode and deletes ciphertext after the outer
transaction commits. Failed writes compensate newly written ciphertext; a bounded
orphan sweep handles ciphertext left by a later outer rollback. Audit and live
events contain identifiers/revisions only, never document bodies or filenames.

Deliver the domain and tenant/storage tests first, then the additive typed API,
responsive staff authoring and private native readers. This specification does
not describe those unimplemented consumers as complete. Dedicated group threads
and moderated replies are the next independent group-communication increment.

## Attachment consumers

The current native activity reader opens attachments inside its account/school
route boundary. Server capabilities independently control upload and removal;
family readers have neither control. Authenticated bytes use the shared owned
private document renderer: bounded JPEG/PNG/PDF display, no global image cache, byte
zeroization and decoded-image disposal on close, suspension and scope retirement.
PDF completion callbacks are tied to the same reader generation.

Selecting an OS file disposes the old group detail, document list and viewer
before awaiting the picker. Only purpose IDs survive. On return the reader checks
its scope, a monotonic 60-second selection deadline and fresh upload capability.
A foreground upload owns one UUID/revision/byte intent until confirmed. Unknown
transport results permit only an exact retry; background or context exit erases
the selected bytes. The transport's separate bounded copy cannot be mutated by
UI cleanup while an already-started request is sending. Removal similarly uses
one foreground intent and an inline confirmation, without a detached dialog.

The staff panel uses the same service for listing, upload, private download and
removal. Private response middleware also wraps failures and POST responses.
Validation returns errors directly without flashing names/documents. These
purpose routes derive year authority from the material instead of the header's
selected year: current writable scope remains mandatory for upload, while removal
is retained in read-only mode. The panel module check is read-only and the domain
authorizes every upload as a write. Existing activity management links to each
material's document page; this does not grant whole-school management to coaches.
