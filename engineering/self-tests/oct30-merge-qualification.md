# October 30 merge qualification

This candidate preserves the original commits from PRs353,354,357–379 in the integration branch. PRs376–379 are incorporated with their source commits and undergo the same qualification.

Corrections before merge:

- HQ training uses a FormRequest with the platform accounts.manage authority and the existing service validation rules. An omitted optional attendee count is safe.
- Staff onboarding counts invitations only for the same currently active staff population used in its denominator, so departed employees cannot complete replacement staff onboarding.
- Staff import previews use the explicit acting user passed to the service rather than whichever user happens to be authenticated globally.
- Regression coverage rejects teacher imports without teacher-create, foreign-school roles, and replay of checked imports by another actor/school. Counts of accounts, memberships, invitations, teachers and staff stay unchanged on rejection.

Executed on the initial integrated source: SchoolImportsTest and HqOnboardingTest, 19 tests and 146 assertions passed locally. The subsequent HQ permission changes passed 63 tests and 673 assertions (PlatformRolesTest, SaasPanelTest, HqOnboardingTest and SchoolImportsTest). Both Larastan configurations and the full Pint check passed on the initial source. Broader and incoming-change qualification remains pending.

Incoming corrections before merge:

- Season rollovers lock the school before checking destination groups, preventing different actors from creating duplicate trees; existing source labels retain their 100-character contract.
- Bulk homeroom fill uses its established policy through a FormRequest, with service and HTTP wrong-school tests and unchanged membership counts. It serializes membership checks with other assignment writes.
- New-year welcomes exclude explicitly recorded leavers even if their dated enrolment remains.
- LURITS claims tolerate archived learners; all new write endpoints use FormRequests with their relevant authority.
- Ended activity seasons offer no suggestions with inverted allocation dates.

The initial full PHP run encountered a full isolated temporary database. Its results are not counted as passing. The disposable local database was rebuilt with more capacity, separate undo tablespaces and undo truncation; the full suite is rerun against the combined source.

GitHub Actions jobs are queued with no runner assigned and no running workflows observed. An Ubuntu24.04 diagnostic was also queued; the temporary probe workflow has been removed. No production workflow or infrastructure was changed by the probe. No queued check is represented as passed.

The formal Security Diff Scan workflow could not be initialized: its referenced config-preflight resource and scan tools are unavailable in this host. No formal scan report or exhaustive security coverage is claimed; direct code review and repository security tests remain part of qualification.
