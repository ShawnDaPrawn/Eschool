# Legacy profile backfill

Provide a resumable, school-specific dry run and explicit apply command for the
first transition from non-deleted legacy profiles to encrypted profile values. This is
an additive backfill, not a declaration that legacy storage has been retired.

An operator selects one school and learner, parent or teacher records, a source
record ID cursor and a bounded batch. The default is a dry run with no writes,
including no audit/outbox writes. Apply creates deterministic field mappings via
the existing legacy_form_field_id FK, validates values with the same rules as
the shared form, and inserts missing encrypted values. Existing rows, including
explicit clears, always win. Lock the person before the source row and field,
matching live profile writes. Re-running or restarting a batch is safe.

Imported definitions start hidden, sensitive and encrypted. A school admin
reviews their roles before publishing them in the existing profile editor.
Never revive deleted categories/definitions or overwrite later schema edits.
Religion copies only into its optional hidden encrypted default. Do not copy
the students.caste column, executable HTML attributes, applicant fields, or arbitrary file paths.
With --files, supported local attachments can be validated and copied into
private storage; without it, files remain explicitly reported as pending. Old public
objects and legacy JSON/columns remain intact until compatibility writes and
installed-client validation allow their separate retirement.

Reject ambiguous field names, malformed JSON, conflicting repeated keys across maps,
unknown populated keys, invalid types/options and user/school mismatches. Report
only counts, numeric record/field IDs and fixed issue codes: no private values,
names, options, filenames or validation exception text. Report issues without
blocking unrelated records; a batch with unresolved issues exits nonzero.
Progress is per selected profile, with last source ID and has_more. Advancing
past an issue does not resolve it; repair/re-run that source row separately.

## Verification

Cover dry-run immutability, encrypted at-rest storage/audits, three profile
types, two schools, mismatched source ownership, hidden/sensitive access,
preservation of newer values and clears, restart/cursor behaviour, schema
edits/deletions, malformed/ambiguous inputs and pending files. No new HTTP route,
public contract, table, cloud service or scheduled job is introduced.

## Operator workflow

Back up SQL, private ciphertext and APP_KEY together; restore and rehearse on an
isolated copy first. Run `php artisan profiles:backfill-legacy --school=1
--profile=learner --limit=500` (one line) and review its fixed-code report. Add
`--apply` only for the reviewed batch. Continue with `--after=<next_after>` for
the same profile; repeat for parent and teacher. Keep reports with the migration
record, repair all unresolved rows, review field visibility in Settings, then
exercise old-client and new-form workflows before any storage retirement. Add `--files` to validate/copy legacy attachments
using the [private-file migration rules](profile-legacy-files.md). Public source
objects remain in place pending the separate compatibility/retirement transition.
