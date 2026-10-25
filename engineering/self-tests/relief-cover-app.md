# Relief cover shared app self-test

Teacher phone and desktop use the same scoped cover workbench. Named teacher and
substitute choices, current cover history, private absence reasons, explicit
assignment/refresh/cancellation and assigned-lesson views use the typed v2 API.
Unknown write outcomes retain the exact UUID and immutable payload for a deliberate
retry. Private text is memory-only and cleared on a server access refusal.

Every request sends the pinned X-Account-Id and X-School-Id. Repository replies are
rejected after a token, account, school, role or module change. Every MeContext
refresh unmounts the reader and rechecks server configuration, including when the
visible context fields stay identical after a permission change. App suspension
hides the page until access is freshly checked. Read-only modules keep browsing
available and disable mutations. The backend remains authoritative.

Validation: the combined shared academic package passed 204 tests and the Teacher
app passed 40 tests after merging activity groups and expected-account binding.
The subsequent cover privacy cohort passed 12 tests, including same-account role
and module revocation, unchanged-context permission withdrawal, read-only controls,
late responses, immutable retry and 390/820/1280-pixel layouts. Focused shared
analysis reports no issues; affected Teacher route analysis has no errors or
warnings (three existing style infos). The typed cover contract fragment matches
all nine corresponding canonical operations, including ExpectedAccount references.

Actual full Teacher Chrome flow passed again on the final account-bound privacy
version: real login, home entry, expected-account header, private draft, request
and withdrawal clearing. Physical devices, native iOS,
classroom and staging acceptance are not claimed. Locale catalogue additions
are present; fluent review remains external acceptance.
