# Support plan domain self-test

A dated principal appoints a named professional to a dated learner caseload.
Only current counsellor/safeguarding capability plus that appointment can open
or change the year-spanning SIAS plan. Principal assignment metadata carries no
clinical body. Teacher access is limited to an explicitly approved, named,
expiring accommodation projection; it does not decrypt the support-plan body.
Changing accommodations withdraws old disclosures. Reviews and versions retain
history; closure/revocation never deletes it. Private text is encrypted and
excluded from generic model serialization and audit fields. UUID receipts hold
model references and keyed hashes rather than copied clinical responses.

Validation: 29 affected tests passed (827 assertions): support happy path,
case owner/role/school refusals, ended enrolment and disclosure expiry, current
role/module/caseload withdrawal before replay, platform operator/impersonation
refusal, encrypted/versioned content, teacher redaction, unknown identifier
collision, context-change rollback and retained-history rollback refusal;
shared restricted access, role conversion, controller response checks, complete
table ownership inventory and migration rollback/backfill rehearsal also pass.
Full configured Larastan and Pint pass. Named learner/staff configuration works
before a new plan exists and filters staff by current school membership.

This increment supplies the domain; API and panel/shared apps follow separately.
No actual learner records, production configuration, safeguarding acceptance or
physical-device testing are implied by these synthetic fixtures.
