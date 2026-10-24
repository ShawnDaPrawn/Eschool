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
| learner_refs | map canonical student numbers to confirmed target learner IDs, all as strings |
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
