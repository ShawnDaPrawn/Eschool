# Private incident photos

Extend the existing incident secret/owner and professional-team boundaries. A
photo is attached only after a report exists, using the private report code in a
POST body; the anonymous route never consults a signed-in actor. Named uploads
also require the current reporter and current learner/guardian authority. Staff
reads require fresh incident-team access; a case or support-plan grant alone
never grants report media. No generic file identifier, original filename,
reporter/device/IP field or external image URL is accepted as authority.

Use the platform UploadScanner for both incoming and re-encoded bytes. Limit
JPEG/PNG input to 4 MiB and sixteen million pixels before decoding; orient using
only the source orientation, re-encode pixels onto an opaque canvas, and create
a bounded thumbnail. Remove location/device/XMP and other original metadata.
Store only encrypted sanitized pixels and an encrypted thumbnail, with bounded
scan evidence. Scanner failure or rejection stores nothing. No raw source or
user filename is retained. These are implementation limits, not a deployment
freeze; live scanner and phone/camera qualification remain separate evidence.

A report-scoped operation UUID and keyed original-byte digest provide exact
retries without storing or associating a reporter identity. Recheck code/owner,
school/module and school context after scanning and before transactional storage;
late revocation or context replacement refuses the operation. Composite school
foreign keys prevent accidental cross-school linkage. Rollback removes orphaned
ciphertext, and retained evidence prevents destructive migration rollback.

The public phone flow offers photo attachment after the initial report, preserving
its private code and existing thread when an upload fails. Authorized thumbnails
are embedded from sanitized data; downloads POST the code, never a capability
URL. Private cache/referrer/CSP headers remain mandatory. Panel/app readers use
the same services and current authority. Private images use an owned decoded
image handle rather than persistent/network image caches. No clinical photo is a
gallery/tag, AI source, transfer attachment or schoolwide live event implicitly.

Verify real MySQL isolation/ownership/revocation, exact retries and changed intent,
scanner rejection/outage, metadata removal/orientation, malformed or oversized
images, rollback cleanup, ciphertext tamper and private HTTP/browser transport.
Synthetic scanner fixtures do not qualify a deployed malware engine.
