# Open standards: OneRoster export and Open Badges credentials

Action Plan 5.9, part 1. Sync with the tools teachers already use rather than
compete with them. This part adds two open-standard outputs on the server side:

1. a **OneRoster 1.2 CSV export** a school starts from its own panel, and
2. **Open Badges 3.0 credentials** for recognition awards, which families can
   download and anyone can verify.

Not in this part: Google and Microsoft sign-in, Google Classroom and Teams
sync, a public third-party API, and app screens (the Flutter apps will call the
new v2 endpoints in a later step).

## OneRoster 1.2 CSV export

### User stories

- As a **school leader** with the sensitive ability *Export the school roster
  in OneRoster format* (`oneroster-export`, people module), I prepare an export
  with a reason, so another system can load our classes, learners and teachers.
- As the same person, I download the ZIP through a link that works for me only,
  for a few minutes, while the export is kept (two days by default).
- As a school, birth dates and sex leave NavigateEd only when we ask for them,
  and only someone who also holds *See and export sensitive profile details*
  (`profile-sensitive-export`) can ask.

### Rules

- **Who.** Staff of the current school (not a parent or learner role), not
  NavigateEd platform staff, not in a support or impersonation session, with no
  pending password change, holding `oneroster-export`. The migration grants it to
  every built-in Super Admin role; custom roles get it through the role editor,
  which shows it as sensitive and audits every grant. The queued job checks the
  requester's ability again before it builds anything.
- **Reason.** 10 to 500 characters, stored encrypted, written to the audit log.
- **Background build.** `BuildOneRosterExport` runs inside the school
  (`CurrentSchool::run`), builds the ZIP, encrypts it with the application key
  and stores it on the private `academic_private` disk under
  `schools/{id}/oneroster/{export}/{uuid}.enc`, with SHA-256 of the ZIP and of
  the stored copy. One export may be preparing at a time.
- **Download.** `open-standards.download` is a `signed` route with a
  `reader` parameter bound to the requester; the link lives
  `ONEROSTER_DOWNLOAD_LINK_MINUTES` (10). The stored copy is checked against
  both hashes before it is sent. Requested, ready, failed and each download are
  audited (`open_standards.oneroster_export_*`).
- **Retention.** The daily `open-standards:prune-exports` command (03:40)
  visits each school in its own context and deletes the stored file of every
  export whose window (`ONEROSTER_KEEP_DAYS`, 2) has ended. The row stays,
  marked `purged` with `purged_at`, its fingerprint and audit trail kept
  (`open_standards.oneroster_export_purged`); a download is then refused (410).
- **School apart.** Every read goes through school-scoped models
  (BelongsToSchool) in the job's school; LURITS numbers come from
  `LuritsNumbers::forLearners`, which refuses any learner of another school. The
  export of school A never holds a row of school B, and school B cannot find or
  download school A's export.

### What the ZIP holds (bulk mode)

`manifest.csv` lists every OneRoster 1.2 file as `bulk` or `absent`, with
`source.systemName` NavigateEd. Data files use the 1.2 CSV binding's headers in
order (`OneRosterTables::HEADERS`); `status` and `dateLastModified` are blank
in bulk mode.

| File | Rows |
| --- | --- |
| orgs.csv | The school (`type` school; `identifier` its EMIS number, else its slug). |
| academicSessions.csv | The current school year (`schoolYear`) and its terms (`term`). |
| courses.csv | One homeroom course per grade, one course per subject and grade of the year's teaching groups. |
| classes.csv | Homerooms with learners placed today (`homeroom`) and active teaching groups (`scheduled`). |
| users.csv | Learners placed today and their current homeroom and group teachers. |
| roles.csv | One primary role per user: `student` or `teacher`. |
| enrollments.csv | Learners and teachers in each class, with begin and end dates. |
| demographics.csv | Only when asked: `birthDate` and `sex` (male, female, other, unspecified). Race, ethnicity and birthplace are never exported. |

**LURITS first.** A learner's `identifier`, `username` and
`userMasterIdentifier` are their LURITS number; without one, `identifier` and
`username` are the NavigateEd number. `userIds` lists both, as
`{LURITS:...}` and `{NavigateEd:...}`. `sourcedId`s are UUID v5 values derived
from the platform URL, school and internal record, so a later export updates
the same records. Grades use CEDS codes (Grade R is `KG`, Grade 1 is `01`).

Never exported: guardians, learners' email addresses and phone numbers,
marks, attendance, health, wellbeing, discipline, and anything of another school.

## Open Badges 3.0 credentials

### User stories

- As a **guardian**, I download my child's badge for an award as an Open
  Badges 3.0 credential, to keep it or share it with a school or programme.
- As a **learner** in a stage the school's rules allow (high school by default,
  `open_standards.open_badges.learner_stages`), I download my own badges;
  younger learners are told to ask their parent or guardian.
- As **anyone given a credential**, I check that it is genuine and not revoked.

### Issuance

- Source: active `award_presentations` (presented awards, outside awards and
  persistence badges from `recognition_badges`). The awards module must be on.
- API v2: `GET /api/v2/open-badges/learners/{learner}/credentials` lists the
  learner's current awards and whether each is issued;
  `GET .../credentials/{presentation}` returns the signed credential as an
  `application/ld+json` attachment. Authority is `RecognitionAuthority::family`
  (active guardian link or legacy parent, or the learner with guardian
  approval), plus the stage rule for the learner. Another guardian, a teacher,
  or a learner or award of another school gets 403 or 404.
- The credential is signed once, on the first download, and the signed JSON
  is stored encrypted in `open_badge_credentials.document` with the `key_id`
  that signed it. Every later download returns those stored bytes unchanged, so
  renaming the learner or the school, or rotating the key, never changes what a
  credential id says. Issue and every download are audited.
- Document: `@context` VC 2.0 and OB 3.0.3; `type`
  `VerifiableCredential, OpenBadgeCredential`; `id` `urn:uuid:...`; `issuer`
  a Profile at `/open-badges/issuers/{school slug}`; `validFrom` the issue time;
  `credentialSubject` an `AchievementSubject` whose `identifier` is the
  **salted SHA-256 of the learner's name** (never the plain name, never the
  LURITS number) and whose `achievement` has a stable `urn:uuid` per award
  definition (or badge kind, or award); `awardedDate`; `credentialStatus` of
  type `1EdTechRevocationList`.
- Proof: W3C Data Integrity, `DataIntegrityProof` with the `eddsa-jcs-2022`
  cryptosuite (JCS canonicalisation, RFC 8785, and Ed25519), `proofPurpose`
  `assertionMethod`, `verificationMethod` a `did:key` of the signing key.

### Keys

- `OPEN_BADGES_SIGNING_KEY`: a 32-byte seed (base64) from the environment or
  Secret Manager, never committed or stored in the database. Without it no
  credential is issued (503) and the list says `available: false`.
- `OPEN_BADGES_KEY_SCOPE`: `school` (default; each school's key is derived from
  the seed with HKDF and the school id) or `platform` (one key).
- `OPEN_BADGES_RETIRED_SIGNING_KEYS`: earlier seeds, used for verification only
  after a rotation.
- Tests generate an ephemeral seed per test.

### Verification and revocation (public)

Outside the web and api middleware (no session or cookie), throttled
60 per minute:

- `POST /open-badges/verify` takes a credential (or
  `{credential, recipient_name}`) of at most 64 KB. It finds the row by the
  credential's own id, checks the proof against the school's trusted keys, the
  issuer, the dates and revocation, and, when a name is given, the recipient
  hash. Only the key recorded for that credential (`key_id`) is trusted, and
  the issuer must match the one stored at issue. The answer is `verified`, `status` (valid, revoked, not_verified), the
  checks, and a summary of fields copied from the submitted credential. It
  never adds anything the credential does not already say.
- `GET /open-badges/status/{uuid}` is the 1EdTech revocation list for that one
  credential: empty, or the credential with "The school withdrew this award."
  The school's private void reason is never shown.
- `GET /open-badges/issuers/{slug}` is the school's issuer Profile (name only).
- Withdrawing an award (`AwardPresentations::void`) revokes its credential in
  the same transaction (`revocation_code` award_withdrawn, audited); a
  credential of an award that is no longer active also counts as revoked.

### Seam for the learner passport (#449)

`AchievementClaim` describes one achievement independently of where it was
recorded; `BadgeCredentials::claim()` builds it for an award and
`BadgeDocuments::signed()` signs any claim. Once the passport is merged it can
describe its achievements as claims and issue them the same way (or bundle them
as a CLR later) without a second credential format.

## Open questions

- Open Badges 3.0 certification names `eddsa-rdfc-2022` (RDF canonicalisation)
  and VC-JWT; this step uses `eddsa-jcs-2022` from the same W3C EdDSA
  cryptosuites recommendation, because RDF canonicalisation needs a JSON-LD
  library the project does not have. Certification would need one of those.
- Whether credentials should carry the learner's plain name (easier for a
  reader) rather than the salted hash (safer for a child). The hash is the
  default; a verifier can check a name with `recipient_name`.
- Whether guardians (role `guardian`/`parent`) should be part of the OneRoster
  export for tools that message families.
