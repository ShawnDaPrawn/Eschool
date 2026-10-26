# Generic Health upload safety correction

The existing generic profile-file route could write a Health attachment without
calling the safety pipeline used by the new family-purpose route. A real-route
regression proved the alternate path: a refusing scanner still returned HTTP 200
instead of 422.

ProfileFiles now resolves and authorizes the field first, then applies one shared
HealthDocumentSafety pipeline to every Health-category upload. Both entrypoints
scan the source, sanitize image pixels and scan the output before encrypted
storage. Scanner outage returns 503, and a field-purpose change during scanning
returns 409 before writing. The family wrapper keeps its additional current-child,
module and final-authorization checks. Existing non-Health profile behavior remains
unchanged. The two existing upload contracts add only their 409/503 error responses.

Evidence: the original scanner-refusal test proved RED (actual 200), then GREEN.
The final generic profile/family/transfer/academic-exit cohort passes 101 tests,
1,141 assertions; whole-app PHPStan, Pint and strict OpenAPI lint pass. Tests cover
unavailable/refused scanning, sanitized metadata-free image output, authorization
before scanning, purpose drift before storage, encrypted files, wrong-role/owner/
school boundaries, final withdrawal rollback, transfer and exit document round trips.
Test scanners are explicit synthetic protocol fixtures; no deployed engine,
retrospective scan of retained files or production acceptance is claimed.
