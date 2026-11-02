# SA-SAMS layout operations

No official target layout is bundled or declared certified. Start from the
school operator's documented import interface/template, not the synthetic test
fixtures. Primary release/manual index checked 24 October 2026:
https://www.westerncape.gov.za/education/sa-sams-support . Its older module
manuals and current release listing do not establish one current universal
external CSV schema. Keep target import qualification separate from rendering.

The UTF-8 JSON object has these required fields:

| Field | Value |
| --- | --- |
| schema_version | integer `1` |
| school_id / session_year_id | exact school and explicit academic year IDs |
| kind | `learners`, `attendance` or `marks` |
| format | `csv` or `xlsx` |
| version / source / import_menu | target version and operator's authoritative layout reference/menu |
| columns | ordered `{header, field}` objects; `literal` also needs `value` |
| learner_refs | optional: map canonical student numbers to confirmed target learner IDs, all as strings, for learners without a LURITS number (see below) |
| on_date | school-local `YYYY-MM-DD` for learners or daily attendance |
| delimiter | CSV comma, semicolon or tab |
| template_sha256 / sheet / header_row | exact XLSX hash, sheet name, header row 1–100 |

Allowed source fields: `learner_ref`, `student_number`, `admission_no`,
`first_name`, `last_name`, `dob`, `gender`, `grade`, `homeroom`, `starts_on`,
`ends_on`, `on_date`, `attendance_code`, `subject_code`, `task_ref`, `score`,
`maximum`, `mark_code`, `literal`. Include `learner_ref`; attendance also requires
`on_date`/`attendance_code`, and marks requires `score`. Selecting an unavailable
field fails; do not select task fields for a learner export.

`value_maps` may explicitly map `gender`, `grade` and `homeroom` source values to
target codes. When supplied, every exported selected value needs a mapping.
Attendance needs `attendance_codes` mapping each encountered internal state
(`present`, `absent`, `late`, `excused`, `first_half`, `second_half`, `holiday`)
to the verified target code. Missing daily entries and duplicate daily entries
fail. No absence/half-day conversion is inferred.

Marks additionally needs `task_id`, `group_id`, target `task_ref` and target
`subject_code`. The group, published assessment, grade, subject and year must
agree. `score` is the raw task decimal; `maximum` is its maximum decimal.
`mark_codes` explicitly maps each non-recorded disposition to the target score
sentinel. Selecting the optional `mark_code` column requires an explicit mapping
for recorded dispositions too. `missing` and incomplete recorded marks always
fail. A sentinel from a synthetic test is not a recommended SA-SAMS code.

Limits: layout upload 1 MiB; template 2 MiB compressed/8 MiB expanded, at most
200 ZIP entries and 512 KiB worksheet XML; 10,000 export rows and 40 columns.
Only one blank, formula/link/macro-free XLSX sheet is supported. Output fields
are text; preserve IDs exactly. CSV expressions and expression-like names/codes
are refused rather than prefixed, because changing an identity changes the
target import. Numeric negative status sentinels are accepted only as explicit
mapping values. No server fetch of a `source` reference occurs.

The private download ZIP contains the dataset and `manifest.json`; request
uploads are temporary and generated scratch files are removed in `finally`.
Audit stores actor/version/counts/hashes, not rows or the identity map. No
public storage URLs or generic app reader endpoints are added. Role/school
authority is checked afresh for each request and every ID query is tenant scoped.

To verify provenance inside a trusted server environment, remove `hmac_sha256`
from the manifest, encode it in its original field order with PHP
`json_encode(..., JSON_THROW_ON_ERROR)`, and compare HMAC-SHA256 using the exact
server `config('app.key')` string. Also compare the dataset SHA-256. Never give
the server key to an operator or put it in a client. Retain operator import
evidence privately by target version; the generated manifest never claims it.

## Learner key: LURITS first (Action Plan 1.0d)

Part of the [SA-SAMS export bridge](sasams-bridge.md); the LURITS number itself is
specified in [LURITS as the primary learner identifier](../platform/lurits.md).

### User stories

- As an academic manager, I export learners, daily attendance and task marks
  keyed on each learner's LURITS number, so I no longer type a reference for
  every learner into the layout.
- As an academic manager at a school where some learners have no LURITS number
  yet (new admissions, pre-school, learners from outside South Africa), I still
  export them, using my confirmed `learner_refs` map for those learners only.
- As the same manager, I see how many learners have no LURITS number, both on
  the export page and in each download's manifest, and can go straight to the
  office's LURITS numbers page to record them.

### Rules (the lead's decisions, 31 October 2026)

- `learner_ref` is the learner's LURITS number when one is recorded on their
  global identity (`learner_identifiers`). This applies to all three datasets.
- A learner without a LURITS number keeps the behaviour from before 1.0d: the
  `learner_refs` entry for their student number. The manual map is **kept**,
  not removed, as the fallback for learners without a LURITS number. It is now
  optional, so a school whose exported learners all have a LURITS number can
  leave it out.
- When a learner has both, the LURITS number wins and the map entry is ignored.
- A learner with neither still fails the whole export, as before. An internal
  id is never used.
- Two rows with the same `learner_ref` fail the export. For example, a mapped
  reference that repeats another learner's LURITS number would merge two
  learners in the target.
- The manifest gains `learners_without_lurits`: how many exported rows were
  keyed on the map. It is a count, so it is safe in the audit log.
- The export page shows "N of M learners have no LURITS number yet" for the
  school. Staff with `student-edit` get a link to the office page
  (`lurits-office.index`); others are told to ask the school office.
- LURITS numbers are read in one batch through `LuritsNumbers::forLearners()`.
  It refuses (404) any learner who is not in the current school, as
  `forLearner()` does, so one school's export never reads another school's
  numbers.

### Privacy

- LURITS numbers appear only in the downloaded dataset, which is the point of
  the export. They never appear in the manifest, the audit log (counts and
  hashes only), logs, notifications, URLs or AI context. Validation messages
  never quote a number.
- The download is permission-gated as before: `SasamsExportRequest::authorize()`
  and the service both require `academic-structure-manage` through
  `AcademicAuthority::manage`, afresh for each request.

### Acceptance tests (`Academics/SasamsExportTest`)

| Given | When | Then |
| --- | --- | --- |
| Asha with a LURITS number (and a map entry), Ben without one but mapped | `test_learners_with_a_lurits_number_are_keyed_on_it_and_the_others_on_the_map`: learners, attendance and marks exports; Ben's map entry removed; Ben mapped to Asha's number | Asha by her LURITS number, Ben by the map; `learners_without_lurits` 1 (0 for the marks export with an empty map); the number absent from the manifest and the audit log; refused without a reference; refused for a shared reference |
| School B's Ben with a LURITS number and Asha's student number | `test_a_school_b_learners_lurits_number_never_reaches_a_school_a_export`: School A exports; School A asks the service for School B's learner | the map reference, no School B number or name; 404 |
| Asha with a LURITS number | `test_the_page_counts_learners_without_lurits_and_links_to_the_office_page`: the admin opens the page; a teacher opens it and posts a download | "2 of 3 learners have no LURITS number yet." and the office link, no number shown; 403 twice, no export audited |
| The earlier tests | unchanged | still pass; their learners have no LURITS number, so they still use the map |

### Not in this step

- A separate `lurits` column field, for layouts that want the LURITS number in
  its own column next to another learner reference.
- Reading LURITS numbers back from SA-SAMS export files other than the learner
  list (the onboarding learner import does that; see
  [school imports](../people/school-imports.md)).
- Confirming the official LURITS format (`identity.lurits.pattern`).
- No API or OpenAPI change.
