# Private wellbeing inbox API

The dedicated Facilitator and family readers can list delivered wellbeing notice
metadata without a TeacherModel or Firebase dependency. Every request pins school
and expected account. Recipient linkage and the fresh private source predicate
apply before pagination, unread totals and read acknowledgement. The optional
staff/reporter selector only narrows that authorized query. Responses contain
fixed generic text and implemented private-area links, never report/case IDs,
names, category, urgency, body, photos or private codes. Errors and successes
remain no-store/private.

A per-recipient first-read marker is immutable on repeat acknowledgement. It
remains available during module read-only access; account/school/role, dated
assignment, family approval, ownership and enrolment withdrawal refuse old
notice IDs. Undelivered intents are absent. The read-marker migration refuses
destructive rollback after retained receipts exist.

Focused inbox/domain/public-page/photo API and dependent group migration tests:
30 passed, 765 assertions. The first route-matrix pass had 42 passing scenarios
and one unreachable photo placeholder: a numeric sample failed the UUID route
constraint before authentication. The fixture now provides a syntactically valid
UUID, preserving the product constraint and allowing the auth check to execute.
Strict OpenAPI lint passes. Further merged checks and SDK evidence follow below.

This stack includes the shared notification outer-commit fix (#163). Live push,
email delivery, deployed queues, physical phones and store builds remain separate
qualification. Client inbox rendering is a follow-on source slice.

The accompanying CI corrections cover explicit stateless public reporting scope,
all declared facilitator/public lifecycle exceptions with reasons, a group
rollback rehearsal that removes newer dependent migrations first, M3 public-form
buttons without new baselines, and a smoke-only offline lease relative to the
current test clock. Production offline expiry behavior is unchanged.

Final backend qualification passes all 43 affected tenancy route scenarios
(314 assertions), all 389 global OpenAPI contract cases (1,803 assertions),
and whole-app Larastan with no errors. With the earlier targeted cohort this
is 462 passing test executions / 2,882 assertions. Public boundaries now reject
an unknown school before validation; actual positive public upload/report flows
remain covered. No access or data constraints were removed for these checks.

The coordinated SDK additions also preserve optional annual category/year group
mapping for recognition and optional authorized historical-year choices/current
year bootstrap for family discipline. Their domain/UI implementations remain
owned by the corresponding streams. Existing canonical paths and models remain.

Pinned generation produces 1,539 Dart files; a second full generation is
byte-identical. All 96 SDK tests pass, including explicit unread null and first
read timestamp decoding. SDK analysis reports zero errors/warnings and 852
existing/generated style informational messages. All previous canonical
path/schema names remain; wellbeing, recognition and discipline owned fragments
match the combined canonical definitions exactly. Strict OpenAPI lint passes.
