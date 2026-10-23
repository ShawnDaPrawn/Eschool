# Guardian access approval

Parents see only children linked to them at the selected school. An active,
verified guardian with approval rights can approve the current consent text or
withdraw access. Withdrawal ends all active approvals, revokes learner tokens
and database sessions, and is audited. Removing a link, disabling a guardian or
removing email verification invalidates that guardian's approval immediately.

The migration imports relationships from the legacy parent columns, never
consent. School identity is resolved by the existing membership middleware.
Decisions are serialised per learner and repeated approvals are idempotent.

Routes: GET `/api/v2/guardian/approvals` and POST
`/api/v2/guardian/approvals/{learner}` with `decision` and `consent_version`.
Wrong school and wrong owner return 404; wrong role/unverified guardian 403.

Enforcement is staged with `LEARNER_APPROVAL_ENFORCED`. It is disabled on
existing installations until guardian onboarding is deployed and verified;
there is no automatic legacy-consent exception when enabled. New work and
tests use enforcement enabled. Production enablement needs the owner's
acceptance of the guardian onboarding and existing-family transition.

Automated evidence: `GuardianApprovalTest` covers approval, duplicate requests,
withdrawal, stale consent, wrong guardian, wrong school, wrong role, removed
relationships and token creation. Staging/device self-testing remains pending.

## Guardian onboarding

School staff with learner-create permission can email an existing linked
guardian an invitation. The staff answer never includes its secret. The
64-character random token is hashed for lookup and encrypted at rest for
reminders; encrypted queued mail carries it to the verified mailbox. Preview
and acceptance require the secret, refuse expired/replaced/used links, and
check the school and account are active. Acceptance verifies the mailbox and
signs the guardian in, but does not approve any learner. Unverified accounts
must set a password under the shared policy. Changing a password revokes
existing sessions and tokens. Reminders are claimed once on day three and on
the seventh day (six complete days, before the seven-day expiry).

The existing shared approval screen now decodes the generated wire models;
its UI view models and the shared API transport remain in place. New
invitation endpoints use the app's existing contract. Staging delivery, email
provider verification and a real-device onboarding run remain pending.

Mailbox binding and queued authority checks are specified in
[invitation lifecycle](invitation-lifecycle.md). Changing a guardian email clears
its verification and retires existing email links. Legacy unbound email links
require reissue after the binding migration; printed codes keep their existing
expiry and purpose. Password setup never grants learner-access consent.
