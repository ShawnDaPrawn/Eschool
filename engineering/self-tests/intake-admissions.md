# Intake and admissions self-test

Implementation: public phone form, encrypted partial drafts and private resumes;
scanned private supporting documents; profile-engine mapping; office pipeline,
bulk offers, capacity reservations, FIFO waiting list, exact-cent office fee
acknowledgement, balanced placement and atomic individual/bulk enrolment. Guardian
invitation follows commit; application email proof never approves a learner.

The backend cohort covers wrong school, owner and role; before-body authority;
changed sensitivity/field types; private validation without session flash; stale
account/year pins; document retry/encryption/scanner outage; accepted reservation
past deadline; expiry/decline promotion; batch rollback and retry; draft/enrolled
copy retention and orphan erasure without canonical-record deletion. **30 admissions checks / 208 assertions** passed after the final retention-edge
fixes; whole-application Larastan and selected Pint pass. The preceding integrated
controller/schema/language/message-engine/recipient-role cohort passed76checks
(28,053assertions). TypeScript and browser JavaScript syntax checks also pass.

Actual Chromium passed the no-account draft → queued encrypted mail resume →
contact proof → submit → office bulk offer → family acceptance → office fee →
enrolment → guardian-invitation guidance flow at **390, 820 and 1280 pixels**.
All three passed accessibility (serious/critical) and horizontal overflow checks,
with no collected public-page errors. The run took39.7seconds. The first run
caught a real numeric-throttle namespace collision; separate named admission
limits fixed it without disabling spam controls. A regression exercises repeated
normal form navigation alongside the independent creation allowance.

The email helper reads synthetic encrypted Laravel queue jobs in a guarded local
`_test` database. This proves actual mailable rendering/queueing and link exchange,
not an external SMTP delivery. File tests use the platform scanner interface with
synthetic clean/refusal/outage responses. A deployed scanner service, real-device
checks, independent first-time users, staging and fluent translation review remain
external qualification. No production/store/payment/scanner-provider activation
or whole Phase 4 completion is claimed by this feature.
