# Private incident-photo domain — 25 October 2026

This increment implements private storage and authorization services, not yet
public/panel/app upload controls. Photos attach to an existing report through
its private code; named reports additionally require the current original
reporter. Professional reads require independently dated incident-team access.
Admin, Teacher, platform and revoked-team paths return no photo bytes.

JPEG/PNG input is bounded before decoding. Real synthetic JPEG verification
proves orientation correction and removal of original EXIF/device/location data.
Both original and sanitized bytes pass the platform scanner interface. Only
sanitized encrypted pixels, an encrypted thumbnail and encrypted scan evidence
are retained. Source filenames and reporter/device/IP fields are not stored.
School-scoped operation UUIDs and keyed byte digests deduplicate exact retries;
changed intent is refused. Successful attachment advances the report revision.

School/module/code authority is rechecked after scanning and inside the school
transaction. Late identification, password readiness and module changes refuse
storage; a scanner changing school context is refused and the original context
restored. Database composite foreign keys also reject cross-school linkage.
Failed row creation rolls back the report revision and deletes orphaned
ciphertext. Path and ciphertext tampering refuse reads. Read-only mode keeps
existing authorized reads while refusing new uploads. Five-photo and input size/
resolution bounds protect availability. Retained records prevent destructive
rollback; the original wellbeing rehearsal now reverses its photo dependency.

Actual results: 11 real MySQL/privacy/migration tests / 68 assertions pass.
Whole-app Larastan has zero errors; changed PHP passes Pint. Scanner fixtures
are explicitly synthetic. A deployed malware engine, camera/device behavior,
image quality, ingress/log privacy and latency are not qualified by these tests.
HTTP transports, public follow-up controls, restricted panel/app previews and
fresh-authority notices continue in the next increments; the module defaults
remain unchanged.

Review correction: an authenticated caller holding an anonymous code no longer
appears as the actor of a photo event. EXIF transpose/transverse orientations now
use the correct flip after rotation. Both findings were reproduced against the
reviewed source (actor link plus orientations 5 and 7 failed), then corrected.
An asymmetric six-colour grid verifies all eight orientations in both stored
pixels and thumbnail. Final isolated cohort: 20 tests / 390 assertions, no test
warnings; whole-app Larastan and Pint pass. The first review worktree run lacked
its ignored local test `.env`; restoring that existing test setup removed the
file-read warnings before the final clean run.
