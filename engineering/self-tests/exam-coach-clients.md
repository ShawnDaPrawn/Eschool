# Exam Coach client verification

Teacher, dedicated Facilitator Teacher and Student screens now use the existing
private typed contract for licensed paper editing/publication, exact version
reads, question/memo PDFs, count-back study plans, approved cropped working,
provisional feedback and explicit reasoned teacher confirmation/correction.
Parent accounts have no Exam Coach entry or direct-route authority. No official
marks are posted by these screens.

Exact submitted writes are immutable. An unresolved write retains its operation
locator and a scoped, encrypted, in-memory intent for at most fifteen minutes;
unknown receipt 404s do not create replacement UUIDs. Rendered private bodies and
unsubmitted edits disappear on background/context changes. Only the approved
crop is retained and uploaded, with expiry checks before rendering.

Evidence so far:

- The final retained focused suite contains 58 passing repository, screen,
  pending-intent, Family entry/route/locale and byte-protocol cases. The actual
  run passed 59 cases including one temporary native diagnostic, which was
  retained privately rather than published as a second web UI harness.
- Actual classic Teacher Chrome passes licensed draft/save recovery,
  publication, provisional confidence, exact review recovery and withdrawal.
- Actual dedicated Facilitator Chrome passes its fresh feature-withdrawal and
  closed legacy-auth-box assertions, plus the complete Teaching Assistant flow
  using the corrected mobile fixture.
- Actual Student and Parent Family Chrome journeys both pass. Student library →
  exam count-back plan → explicit sharing and crop approval → private upload →
  provisional confidence/feedback → withdrawal passes with exact account/school
  headers. Parent entry/direct-route denial makes zero Exam API requests.

The combined Family run also passes published-topic Tutor checkpoint recovery.
Its structured results record all three individual journeys as passed. A real
web-only bug was reproduced: WebCodecs rejects the immutable typed-byte view
that protects a human intent. The decoder now receives its own bounded temporary
native byte buffer, cleared after use, while the original intent remains frozen.
The direct production WebCodecs → cropped PNG → encrypted intent → multipart
regression passes in Chrome. No crop, byte, geometry or privacy guard is relaxed.

Native Android and physical camera checks, real-provider evaluation, school
licence approval and production release are not claimed. External inference
remains disabled unless separately qualified and explicitly configured.
