# Phase 3/4 review corrections

The review cohort covers durable intake occupancy after private application
retention, selected parent identity import without changing a shared existing
identity, current-year group mappings for activity-only award readers, and UTC
transfer deadlines across source/receiving school clocks. The data correction
only converts true transfer instants, in one transaction, using each owner's
configured clock; it preserves local academic dates and signed frozen payloads.
Populated rollback refuses to discard the corrected semantics.

The recognition browser flow now waits for a real authenticated counter poll.
Its same-origin referrer override allows Sanctum to recognize the existing panel
cookie while the enclosing private page keeps its no-referrer response policy.

The full CI browser run exposed a production environment label inherited from
.env.example and an in-flight old class-picker callback during year switching.
The browser job explicitly uses local mode and its reserved disposable database;
the production fixture refusal remains unchanged. The year-switch regression
holds the initial class response until the write starts, exercises that callback
while the write is delayed, and retains the no-premature-read assertion.

The focused PHP cohort passes **114 tests / 1,016 assertions** on MySQL,
including the existing admissions, awards and transfer workflows and rollback
checks. Selected Pint and whole-application Larastan pass. TypeScript and browser
JavaScript syntax checks pass. Actual Chromium passes all **seven flows in 1.7 minutes**: admissions and
recognition at390/820/1280 plus the controlled in-flight year-switch regression.
Recognition waits for a successful real protected counter poll at each width;
admissions includes queued-email resume through atomic enrolment. All original
accessibility/overflow/private-reason assertions remain.
This is not a phase completion, production, real-device or external-provider
acceptance record.
