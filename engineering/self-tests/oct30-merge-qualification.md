# October 30 merge qualification

This candidate preserves the original commits from PRs353,354,357–379 in the integration branch. PRs376–379 are incorporated with their source commits and undergo the same qualification.

Corrections before merge:

- HQ training uses a FormRequest with the platform accounts.manage authority and the existing service validation rules. An omitted optional attendee count is safe.
- Staff onboarding counts invitations only for the same currently active staff population used in its denominator, so departed employees cannot complete replacement staff onboarding.
- Staff import previews use the explicit acting user passed to the service rather than whichever user happens to be authenticated globally.
- Regression coverage rejects teacher imports without teacher-create, foreign-school roles, and replay of checked imports by another actor/school. Counts of accounts, memberships, invitations, teachers and staff stay unchanged on rejection.

Local qualification completed on 30 October 2026:

- Combined feature, HQ and import regression group: 83 tests, 798 assertions passed.
- Family reach after current membership/Parent-role corrections: 4 tests, 24 assertions passed.
- Independent-connection season rollover lock regression: 1 test, 5 assertions passed.
- Final school and HQ invitation/FormRequest regression group: 12 tests, 137 assertions passed (including the six platform-role tests repeated from the combined group).
- Shared Flutter academics package: 762 tests passed; generated API client: 182 contract tests passed. Both analyzers have no errors or warnings (existing informational lints remain).
- OpenAPI client regenerated with the pinned generator: all 2,838 generated Dart files match the committed source exactly; no package diff.
- Both Larastan configurations passed on the combined source; general analysis also passed after the final staff-request changes.
- Initial full Pint and the additional changed-PHP checks passed. Configuration, route and Blade caches build and clear successfully.
- Gitleaks scanned all 79 added non-merge commits through 62fb7de59 with no leaks. This is the added commit range; the separate GitHub whole-history scan remains pending.

The focused Playwright flow run passed all 28 tests (18.3 minutes): timetable, exam periods, short days, modules/role editor, navigation/access review, profile editing and private files, guardians and HQ at phone/tablet/desktop widths. The full backend shards remain in progress and have reported teaching-draft failures. The 21 teaching-draft tests pass as an isolated class, while a larger sequence reproduces a script-marking failure; investigation remains open and these failures are not waived. The backend run uses the combined feature source, with the later invitation request changes separately covered by the staff regression group above. The first browser attempt had an incomplete local storage/scanner fixture and resource-related timeouts; it is not counted as passing. A rerun uses the repository's synthetic scanner protocol fixture (not a malware-detection qualification), a fresh sign-in setup and a longer local default test timeout without altering CI.

Incoming corrections before merge:

- Season rollovers lock the school before checking destination groups, preventing different actors from creating duplicate trees; existing source labels retain their 100-character contract.
- Bulk homeroom fill uses its established policy through a FormRequest, with service and HTTP wrong-school tests and unchanged membership counts. It serializes membership checks with other assignment writes.
- New-year welcomes exclude explicitly recorded leavers even if their dated enrolment remains.
- LURITS claims tolerate archived learners; new write endpoints use FormRequests with their relevant authority. Platform staff changes and both kinds of one-time staff invitation acceptance follow this boundary too.
- Ended activity seasons offer no suggestions with inverted allocation dates.
- Family reach excludes inactive school memberships and requires a current local Parent role before counting an existing parent-app token; verified email remains available to a guardian with another active school role.

The initial full PHP run encountered a full isolated temporary database. Its results are not counted as passing. The disposable local database was rebuilt with more capacity, separate undo tablespaces and undo truncation; the full suite is rerun against the combined source.

GitHub Actions jobs are queued with no runner assigned and no running workflows observed. An Ubuntu24.04 diagnostic was also queued; the temporary probe workflow has been removed. No production workflow or infrastructure was changed by the probe. No queued check is represented as passed.

The formal Security Diff Scan workflow could not be initialized: its referenced config-preflight resource and scan tools are unavailable in this host. No formal scan report or exhaustive security coverage is claimed; direct code review and repository security tests remain part of qualification.
