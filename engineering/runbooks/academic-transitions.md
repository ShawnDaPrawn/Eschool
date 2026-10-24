# Dated learner changes and exit exports

Use **Academics → Mid-year learner changes**, find the learner and choose a year
and Join, Leave or Subject change. Review the date, homeroom, chosen groups,
mark rule and reason; preview before applying. A stale preview requires another
review. Do not edit canonical transition facts or use legacy imports to undo a
recorded departure. Use a later explicit Join for re-entry. A leave date is the
last attended day, inclusive; learner app requests stop the following day.
Parent, staff and other-school identities remain independent. Guardian approval
is still required for learner activation. A pro-rata request records intent for
finance only and posts no invoice, payment or journal.

Grade defaults start at catch-up, can be changed to excused, and allow carried
marks on subject changes only within the same subject/grade/year. Carried reuses
canonical task marks without copying. Another subject requires catch-up/excused.
An office override is explicit and audited. Group choices determine the timetable;
an empty initial join allocation can be completed later.

## Exit package

Only academic managers with `profile-sensitive-export` may prepare a package.
Choose a recorded departure, released report PDFs and current permitted profile
documents explicitly. Preview includes released mark summaries (not unreleased
capture), captured attendance through the earlier of today/last attended day and
retained enrolment history. No missing attendance is represented as present or
absent. Verify selection before queueing. Run a normal queue worker. Refresh the
page to see Queued, Ready, Failed or Expired. Download links last at most ten
minutes and never exceed copy expiry. Copies belong to the requester and require
fresh school, role, sensitive-export and document-field rights on each request.
Use another new package if another authorized officer needs a copy.

A changed/unavailable source or revoked requester fails closed; no partial ZIP is
published. Retry is safe only for an unchanged failed package. Source changes need
a new preview/new operation UUID. A stale worker generation cannot overwrite a
retry or completed copy. If private storage is missing/corrupt, restore the exact
ciphertext with its matching database/key or prepare a new copy. Never regenerate
and overwrite a released report as part of recovery.

ZIPs contain `manifest.json`, its base64 detached Ed25519 `manifest.signature`,
`verification.json` and checksum-listed JSON/PDF/document files. Verify the exact
manifest bytes with the Ed25519 public key, then every listed file SHA-256 and
byte count. Compare the ZIP SHA-256 and signing-key fingerprint against the
school's authenticated export page or a separately authenticated hand-off.
The public key inside a ZIP alone does not establish the school's identity.
Keys are deterministically derived per school from APP_KEY using a separate HKDF
purpose; rotation changes new-package keys, while old packages retain their
public verification key. Keep APP_KEY private and in the existing backup/rotation
procedure. PHP sodium and zip extensions are required (covered by CI image).

Scratch is mode 0700 with temporary files mode 0600 and removed on success/failure.
Stored ZIPs and private blueprint/manifest values are encrypted. Audit and jobs
contain IDs/state/hashes, never document bodies or learner reasons in plaintext.
The 50 MiB uncompressed/archive cap and selection limits prevent unbounded jobs.
An oversized learner history requires a separately reviewed scoped export.

## Retention and rollback

The school chooses 1–30 days for **new export copies**, default 7. A nightly
`ApplyRetention` job expires artifact ciphertext and private package payloads,
retaining minimal receipt/audit hashes. It includes inactive schools and cleans
abandoned export scratch older than an hour. Earlier copy expiry is stored on the
package; changing the rule does not silently prolong it. Downloaded copies remain
with their recipient. Confirm the receiving school's handling separately.

`retention_rules` explicitly retains canonical educational records until an
approved wider schedule exists. Leaving never deletes learners, approvals,
reports, marks, attendance or cancelled future enrolments. The broader Phase 1
retention-policy acceptance is still outstanding; do not invent legal periods.
Restore drills and staging quarantine must include these new school-owned rows
and the academic_private disk. Production rollout still requires acceptance.

The migration refuses rollback once transition/export/cancelled-enrolment history
exists. Roll forward fixes; back up database plus private disks and APP_KEY first.
Do not force-drop the tables to bypass the history guard.
