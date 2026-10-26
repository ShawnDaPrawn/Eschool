# Private activity material files — domain

The domain implements up to ten PDF/JPEG/PNG attachments per authored material,
with current school/group/material/author and password-readiness checks before
processing private sources. Current students and guardians read only published
materials through fresh activity membership, Student membership, enrolment and
approval checks. Another assigned coach may read drafts but cannot edit the
author's attachments without the explicit management permission.

Originals and sanitized image pixels are scanned; unavailable safety checks fail
closed. Stored bytes and filenames are encrypted, object paths are generated and
scoped, and reads recheck publication/current membership plus file identity,
revision, length and digest. Exact operation retries have one effect and material
revisions advance with attachment changes. Removal remains available in read-only
mode and deletes ciphertext only after the real outer commit.

Unreachable encrypted objects left by a later outer rollback are inaccessible to
readers. The daily `activities:prune-material-files` command removes old orphans
and failed-deletion residue in bounded school batches, preserving fresh objects
and other schools. Retained metadata blocks destructive migration rollback.

Evidence:

- File domain plus existing activity and tenancy inventory cohort: 52 tests,
  1,560 assertions pass. Covers owner/role/school/material/publication boundaries,
  ended Student membership, role conversion, scanner refusal/unavailability,
  sanitized image output, exact retry/conflict, revision/quota/size limits,
  password readiness before decryption/scanning, final allocation withdrawal,
  read-only removal, outer-commit deletion and orphan isolation.
- Full MySQL tenancy down/up/backfill rehearsal passes (1 test, 300 assertions).
- Whole-app PHPStan and Pint pass.

This increment introduces no HTTP route or generated SDK change. Typed API,
panel/native attachment consumers and group threads/replies follow separately.
Deployment/private-disk persistence and the actual scanner service remain external
qualification; synthetic scanner tests prove the protocol and refusal behavior.
