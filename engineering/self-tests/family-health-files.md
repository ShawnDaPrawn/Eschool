# Purpose-scoped Health attachments — 26 October 2026

Two private v2 operations connect upload/read of configured learner Health file
fields. They reuse encrypted profile-file storage and the existing profile field
policy; they do not introduce a second medical record or a new clinical grant.
The FamilyForms module, current learner membership/enrolment/approval, actual
current guardian or explicit health permission and permitted Health field are
checked before the file is processed. Source authority is checked again before
storage and before commit; a failed final guard rolls back metadata and removes
the newly written ciphertext. Reads recheck the current file reference and scope
after decryption, so cleared/replaced or non-Health attachments cannot be served.

Uploads fail closed when scanning is unavailable. PDFs up to 5 MB are scanned;
JPEG/PNG images up to 4 MB are scanned, sanitized to pixels, and scanned again.
Private document bytes and original medical values never enter logs or notices.
The Health schema now disables edit controls when the module is read-only.
Generic profile-file callers retain their existing behavior; the new optional
storage authorization callback does not grant permissions.

## Evidence

- Real MySQL/storage HTTP cases cover PDF/JPEG upload, encrypted bytes/names,
  current authorized download, clearing, wrong guardian/school/platform identity,
  disabled/read-only modules, scanner outage, withdrawal during scanning and a
  final post-storage refusal with ciphertext cleanup. A non-Health file with
  deliberately corrupt ciphertext is refused before decryption. Existing generic
  profile-file tests are included.
- The initial broad route/role/contract run identified two fixture URLs that used
  a numeric file value. The exact UUID.pdf sample now reaches authentication;
  the focused role/reachability and tenancy regressions pass without exceptions.
- Whole-app PHPStan/Pint and strict Redocly lint pass. All 395 inherited paths
  and 1,022 inherited schemas remain semantically unchanged; two paths are added.
- Pinned generator 7.25.0 produces 2,111 Dart files. Both complete generations
  have identical bytes for every source and the manifest. All 139 SDK tests pass,
  including actual generated binary download and multipart upload/header tests.
  Dart analysis has zero errors/warnings; inherited generated style infos remain.

The final full typed HTTP/profile/contract/role/tenancy cohort passes: 1,178 tests,
7,468 assertions. These are synthetic
records and a fake scanner protocol; real malware-engine/deployment qualification
is separate. Native Family/Health readers are the next increment; this API change
does not claim their acceptance, parent meetings, gate passes or Phase 4 completion.
