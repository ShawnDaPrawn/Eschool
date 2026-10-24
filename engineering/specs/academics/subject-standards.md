# Subject standards and school subject mapping

Phase 2, step 2. Backend and Admin UX.

## Stories and acceptance

A platform operator imports a versioned CAPS or IEB subject catalogue with a source
URL and effective date. A school admin browses it and explicitly maps an existing
school subject or creates one from a standard. Teachers, parents and learners may
read reference data but cannot edit mappings or the platform catalogue. Imported
standards never silently change existing assessment plans or school subjects.

A repeated identical import is harmless; a conflicting revision is refused and
requires a new version. Validate the whole bounded file before any write. Optional
assessment-component weights use integer basis points and total 10,000. Unknown
weights remain unset; neither a subject name nor a framework implies a ratio.
School users cannot publish or edit global standards. School subject IDs, names,
marks and existing API payloads remain intact when a mapping changes.

## Data and endpoints

Global subject_standards: framework, code, name, phase, grade bounds, version,
source_url, effective_on, optional component weights, publication state and dates.
subjects.standard_id is a nullable restricted foreign key, hidden from v1 raw model
serialization; new v2 resources expose it explicitly.

GET /api/v2/academics/subject-standards supports framework, grade, q and pagination.
GET/PUT /api/v2/academics/subjects/{subject}/standard reads/maps a school subject.
POST /api/v2/academics/subjects/from-standard creates a subject from a published
standard and an owned medium. Writes use fresh subject permissions before
idempotent replay, scoped row locks, FormRequests, services, resources and audit.
The panel provides catalogue search, existing-subject mapping and creation.
The trusted CLI importer is platform administration; no school HTTP route writes
the shared catalogue. Add platform UI management when platform staff access exists.

## Screens, module and notifications

Academics navigation: Subject standards. Material 3 forms, inline error/empty
states, source/version visibility, grade filters and clear mapping actions.
Always-on academics module. No outbound notifications for reference edits.

## Security and verification

School context comes from membership, never payload. Test wrong role, foreign
school/subject/medium, deleted subjects, stale permission replay, invalid weights,
conflicting revisions and transaction rollback. Rehearse migration down/up and
verify v1 subjects do not acquire new raw fields. Validate OpenAPI and regenerate
SDK. Run browser accessibility/overflow and admin/teacher flows. Catalogue
source review and institution-specific policy acceptance remain explicit; no
unverified source data is labeled confirmed.
