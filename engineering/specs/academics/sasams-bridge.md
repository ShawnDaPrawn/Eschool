# SA-SAMS export bridge

Export canonical dated learner enrolments, daily homeroom attendance and task
marks through an explicit, versioned target layout. Do not invent a universal
SA-SAMS CSV or claim a successful target import from a synthetic fixture.
The WCED support page publishes versioned releases and separate module manuals:
https://www.westerncape.gov.za/education/sa-sams-support . The published manuals
do not establish a current universal learner/attendance/marks import schema.

An academic manager supplies a school/year-bound JSON layout with its source,
target import menu/version, ordered columns, explicit external learner references,
and status/code mappings. CSV emits the exact ordered headers and delimiter;
XLSX fills only mapped columns in an exact SHA-256-bound, macro/link/formula-free
template. Unknown/incomplete marks and unmapped attendance states fail visibly.
No absent learner becomes a zero or a present mark. Period registers cannot be
mistaken for daily attendance. Duplicate identities/entries fail rather than
overwrite. Exports never change academic records or activate accounts.

The native panel downloads an export ZIP with its provenance manifest, counts,
layout/output/template hashes and server HMAC. Files are private, never placed
in public storage or logged as learner rows. No paid service or production
release is involved. Fixture layouts prove rendering and isolation only; keep
the school's actual profile/template private and record a successful import into
a backed-up copy of the matching target version before claiming compatibility.

Test happy paths for all three datasets and both formats, role/school boundaries,
exact score strings/leading-zero IDs, missing mappings, ambiguous daily registers,
template mismatch and spreadsheet active content, private downloads and no writes.
Continue unrelated development while target-school evidence remains outstanding.
