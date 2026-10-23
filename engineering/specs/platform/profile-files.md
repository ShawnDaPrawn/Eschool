# Private profile attachments

Extend the shared profile builder with PDF, PNG and JPEG file fields (up to
5 MB). New files belong to a person and definition within one school. Both
metadata names and file contents are encrypted with APP_KEY. Ciphertext lives
below storage/app/private on the existing storage/app mount, outside the public
storage link; it requires no new cloud service.

Uploads use the additive JSON v2 API and acknowledge with null data. Profile
reads expose authorised metadata through a file member of ProfileValue.
Authenticated asset routes serve bytes outside the JSON API, with fresh
person/field rights, school scope, private/no-store, no-sniff and a sandbox.
Neither a submitted storage path nor a file-reference ID is accepted as a
profile write. Replaced, cleared, hidden or removed fields invalidate old links.
Support impersonation cannot read sensitive fields. URLs contain opaque IDs
and extensions, never private names. Audits contain keys/IDs and school only.

The panel and shared apps use the same metadata and upload checks. File upload
must preserve unrelated typed profile edits. Apps fetch with explicit school
scope and auth, then preview images/PDFs from memory. They must not send tokens
to arbitrary metadata URLs or persist private drafts. CSV export emits permitted
file names, and sensitive fields require the existing export permission before
metadata decryption.

Verify wrong school/role/owner, unverified guardian writes, stale references,
file validation, encryption in raw SQL/storage/audits, transaction rollback,
protected asset CORS/cookie/native auth, shared app flows and panel accessibility.
The legacy bridge follows separately; do not claim old public files migrated.
Back up metadata, ciphertext and APP_KEY before migration rollback. Unreferenced
ciphertext remains private pending the Phase 1 retention policy/job.

## User stories

- School admins add a file field and restrict its roles without creating a new
  upload service or exposing a private document through public school settings.
- A verified approving guardian uploads an offered learner document. An unrelated
  parent, another school, and staff without the field's rights cannot read it.
- An authorised owner or staff member opens the same file in the panel and apps;
  replacement and removal revoke its old reference.

## Acceptance tests

Happy-path upload/read/clear, wrong school/role/person, unverified writes, required
files, stale/forged references, encrypted SQL/blob/audits, support exclusions,
failed-write cleanup, cookie/token/CORS and drafts are covered by
`ProfileFilesTest`, shared profile tests, `profile-fields.spec.ts` and the app's
`profile_smoke_test.dart`. Every new v2 operation has a contract scenario; the
route/role and tenancy inventories include the upload routes.

## Data changes

`profile_files`: opaque UUID primary key, indexed/FK school, user and definition,
encrypted name, private path, MIME, extension, byte size and timestamps. User
removal cascades metadata. Definition soft deletion revokes access. Migration
rollback drops metadata; preserve its backup and APP_KEY with ciphertext first.
The tenancy rehearsal includes this migration in reverse/forward order.

## Endpoints

- POST `/api/v2/me/profile/fields/{field}/file` and
  `/api/v2/people/{person}/profile/fields/{field}/file`: multipart upload,
  `profile_type` and `document`; the normal auth/school/role/approval/idempotency
  middleware and fresh service policy apply. Success is the v2 null-data ack.
- GET `/protected/profile-files/me/{file}` and
  `/protected/profile-files/people/{person}/{file}`: authenticated asset bytes,
  same school/role/approval boundary and field policy on every request.
- Existing authorised form reads expose file metadata. Generic writes can clear
  optional files but cannot assign an attachment reference.

## Screens

The panel renders a private download link plus optional clear control. Separate
upload forms use the v2 API and refresh metadata without discarding typed edits.
The shared app form uses the system picker; previews load authorised bytes in
memory and show loading/error/retry states. Cancelled/stale pickers do not write
against another person. Uploads preserve unrelated drafts, including on failure.

## Notification types

None. Existing `context.changed` outbox intent records only the school and
`profile.files` reason; private file names and contents are not notification data.

## Module switch

Identity/profile infrastructure is always on. Field visibility and edit rights
control availability within the active school.

## Security notes

New private disk ciphertext stays outside the public link and uses the existing
persistent storage/app mount. Protect APP_KEY and restore ciphertext/metadata
as a pair. Sandbox/no-sniff/no-store headers protect byte responses. App readers
construct the known protected path rather than trusting a metadata URL.
No new external service, spend, secret or production migration is authorised.

## Self-test steps

Use the two-school fixtures, open an authorised profile, type an unrelated
change, upload a document, view it, save the draft and then clear the file.
Verify its old link is refused; repeat as an unrelated actor and in another
school. Record phone/desktop, native/Chrome and panel evidence in
`engineering/self-tests/p1-profile-files-2026-10-23.md`.
