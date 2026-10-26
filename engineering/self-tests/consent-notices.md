# Current guardian consent notices

The retained implementation connects published activity consent requests to the
existing locked `consent.requested` catalogue and real Parent reader. Notice
text is generic; source payloads carry only the opaque recipient UUID, notice
ID/revision and implemented link. Unique per-guardian source keys, transactional
publication, existing real-commit queue acceleration and rotating bounded recovery
prevent duplicate channel claims and reach guardians verified later.

Native verification: 78 tests / 624 assertions pass across consent lifecycle,
new notices, real Parent HTTP response, Notifier/private-scope compatibility and
context query budgets. Whole-app PHPStan reports no errors; changed PHP passes
Pint. Parent action/model/deep-link and existing form entry/locale checks pass13/13;
changed client sources analyze with no issues. New fixtures were corrected to
provide complete generated context fields, the actual typed AuthCubit provider,
an explicit locale and the rendered Bloc loading frame. The updated existing
Family browser target follows notification → reader → exact signed retry and
health editing; that changed target has not yet run locally and remains CI/browser
qualification, rather than a claimed pass.

The first real Parent notification endpoint test
caught the tenant model's hidden `school_id`; the new action now receives both
the actual school and authenticated reader ID only on consent-request rows.
Historical and unrelated rows preserve their existing serialized fields. Fresh
parent/guardian/learner/event authority is rechecked before delivery and every
inbox SQL execution. Tests also cover answer/cancellation/deadline/readonly,
password/role/platform/context refusals, reference borrowing, later verification,
deleted notices, and rollback.

The Parent action additionally requires the current loaded account, school,
Parent portal, role and enabled module. A trusted family-forms link opens the
existing reader, which independently reauthorizes exact consent wording and
signature. This increment adds no public API operations or generated SDK models.

No deployed mail/push transport or physical-device acceptance is implied by
synthetic notification and app fixtures. The existing request/health journeys were qualified in the earlier form increment;
this notice action has native/widget proof above. Mail/push provider delivery and
the changed full-browser target remain separately recorded qualification.
