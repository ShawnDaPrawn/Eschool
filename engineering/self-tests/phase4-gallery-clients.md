# Private gallery client qualification

Synthetic local fixtures only; no production data or provider activation.

- 33 backend gallery cases: 362 assertions, including owner/role/account/school
  refusals, encrypted pixels, tag consent/removal, exact retries, transfer copies,
  return-episode retention and historical panel write refusal.
- Whole-application PHPStan: no errors at 512 MB; Pint passed changed application
  files. Strict OpenAPI lint passed; regenerated SDK: 108 tests passed.
- Actual panel Playwright at 390/820/1280: 3 workflows passed in 27.6 seconds,
  including upload, private JPEG/no-store response, full viewer, tag proposal,
  withdrawal, post-withdrawal 404, accessibility and horizontal overflow checks.
- 28 shared gallery/widget/repository and transfer-consent cases passed, including
  bounded picked upload/body/pixels and background-preserved unresolved intents.
  These checks cover the pinned
  child, optional retention fingerprint, exact retry, approval capability, late
  response rejection, and background erasure. Later qualification logs record
  the unresolved-intent background regression added during review.
- Actual Teacher, Facilitator, Student and Parent Chrome home entry/reader
  workflows and withdrawn-access erasure passed. Facilitator uses its own client.
- A real HTTP stale-member regression first returned 200 after the learner’s
  Student membership ended despite stale enrolment/group rows; the final guard
  returns 404 and removes that family group from configuration. The full final
  gallery cohort and whole-application static analysis passed after the fix.

Physical-device picker/temporary-file behavior and a real malware-scanner
qualification remain external acceptance work; no such acceptance is claimed.
