# October 30 merge qualification

This candidate preserves the original commits from PRs353,354,357–375 in the integration branch. Subsequent PRs are a separate incoming queue until incorporated and qualified.

Corrections before merge:

- HQ training uses a FormRequest with the platform accounts.manage authority and the existing service validation rules. An omitted optional attendee count is safe.
- Staff onboarding counts invitations only for the same currently active staff population used in its denominator, so departed employees cannot complete replacement staff onboarding.
- Staff import previews use the explicit acting user passed to the service rather than whichever user happens to be authenticated globally.
- Regression coverage rejects teacher imports without teacher-create, foreign-school roles, and replay of checked imports by another actor/school. Counts of accounts, memberships, invitations, teachers and staff stay unchanged on rejection.

Executed: SchoolImportsTest and HqOnboardingTest, 19 tests and 146 assertions passed locally. Broader integration qualification remains pending.

GitHub Actions jobs are queued with no runner assigned and no running workflows observed. A temporary Ubuntu24.04 runner probe checks whether an alternate supported pool can start. No production workflow or infrastructure is changed by the probe. No queued check is represented as passed.

The formal Security Diff Scan workflow could not be initialized: its referenced config-preflight resource and scan tools are unavailable in this host. No formal scan report or exhaustive security coverage is claimed; direct code review and repository security tests remain part of qualification.
