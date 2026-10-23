# Legacy private-file copies — 23 October 2026

The bounded backfill's optional `--files` validates and copies local legacy
PDF/PNG/JPEG attachments into existing encrypted private storage. Without the
flag they remain reported as pending. Dry run remains the default. Source
objects and v1 paths stay intact until the separate retirement transition.

## Verification scope

`LegacyProfileFilesTest` covers opt-in/dry-run behaviour, encrypted SQL names
and ciphertext, hidden fields, authorised protected reads, wrong school/person/
role, all three profile folders, School 1 pre-tenancy paths, traversal/URL/
encoded-path/symlink refusals, MIME/extension/size/disk checks, images, retries,
clears and cleanup after an outer record transaction fails. Existing profile
backfill and schema/form/file/panel regressions pass with it: 85 tests, 515
assertions. The final file class adds explicit School 2 parent-alias assertions
and passes 12 tests, 90 assertions. Pint and Larastan also pass. Exact-head PR CI
must pass before merge; consult its checks for the final results.

Review the operator-only source path and bounded read before apply. No source
file, old JSON or column is deleted. A private copy is not proof that its old
public version has become private. Do not mark storage retirement, old installed
client validation, cloud rehearsal, staging or owner acceptance as complete.

## Operator rehearsal

Back up SQL, public/private objects and APP_KEY. Restore an isolated copy with
delivery quarantined. Run the same explicit-school/profile bounded dry run with
`--files`, review fixed-code reports, then add `--apply` for that reviewed
batch. Validate authorised reads only after field/role review, verify public
source references against their claimed owners, and retain the original backup.
All data used by automated tests is synthetic; no live migration runs here.

The parent editor's actual historical `students/` upload directory is covered
for unprefixed School 1 and prefixed School 2 references. The exception requires
the selected parent's exact source claim; other-person, other-school and
archived claims are refused. Reports still contain fixed codes, never paths.
