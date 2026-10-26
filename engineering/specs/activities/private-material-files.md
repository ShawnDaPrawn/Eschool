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
