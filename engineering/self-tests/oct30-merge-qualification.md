# October 30 merge qualification

The integration candidate preserves the source heads of 25 feature PRs: #353, #354, #357–#372 and #374–#380. Merge PR #373 into main with a merge commit to preserve that ancestry. Source PRs marked merged into the integration branch are not proof that their changes have reached main.

## Corrections before merge

- HQ training uses an authorized FormRequest, safely handles omitted optional attendee counts, and counts invitation acceptance against the same currently active staff population.
- Staff import previews use the explicit acting user. Negative coverage rejects teacher imports without teacher-create, foreign-school roles and replay of checked imports under another actor or school, with account/membership/invitation counts unchanged.
- Season rollover locks the school before checking destination groups, preventing duplicate trees from different administrators. Existing 100-character source season labels remain usable.
- Bulk homeroom fill uses its established policy through a FormRequest, serializes membership checks and has service/HTTP school-isolation coverage.
- New-year welcomes exclude explicitly recorded leavers even if their dated enrolment remains.
- Archived LURITS claimants do not break the office page. New writes, platform staff changes and both staff-invitation acceptance flows use FormRequests.
- Ended activity seasons do not suggest allocations with inverted dates.
- Family reach excludes inactive school memberships and requires a current local Parent role before counting a parent-app token. A guardian with another active school role can remain reachable by verified email.
- Portal tests now model fresh HTTP session stores and guards, with private persisted session files. The CSRF, unknown-origin, role and native-token logout assertions are retained.
- HQ headings wrap long valid titles rather than widening the page, including on phone screens.

## Completed local verification

The 24-feature integration through 9abdda095 passed all four backend partitions, with the two corrected partitions rerun against the financial fixtures' approved isolated database names:

| Shard | Tests | Assertions | Result |
| --- | ---: | ---: | --- |
| 1 | 793 | 9,701 | Passed; 2 conditional integration skips |
| 2 | 1,408 | 22,326 | Passed |
| 3 | 824 | 57,635 | Passed |
| 4 | 1,510 | 35,283 | Passed |
| Total | 4,535 | 124,945 | No failures or errors |

The conditional skips require a live authorized Reverb socket and a real ClamAV daemon with current definitions. They are not represented as passing locally; GitHub's quality job runs them with their integration flags.

Additional evidence:

- Combined feature/HQ/import group: 83 tests and 798 assertions passed; independent-connection rollover lock: 1 test and 5 assertions; current-membership family reach: 4 tests and 24 assertions.
- Final school/HQ invitation group: 12 tests and 137 assertions; portal sign-in suite: 9 tests and 131 assertions; cash handover concurrency: 5 tests and 57 assertions; vendor settlement concurrency: 3 tests and 30 assertions.
- Focused Playwright flows: all 28 passed. These cover timetable, exam periods, short days, modules/roles, navigation/access review, profile saves and private files, guardians and HQ across phone/tablet/desktop widths.
- Shared Flutter academics package: 762 tests passed. Generated API client: 182 contract tests passed. Both analyzers have no errors or warnings; existing informational lints remain.
- The pinned OpenAPI generator regenerated 2,838 Dart files with no package diff.
- Both Larastan configurations, initial full Pint and subsequent changed-PHP style checks passed. Configuration, routes and Blade caches build and clear successfully.
- Gitleaks found no leaks in the added commit range. The separate GitHub whole-history scan remains pending.

The incident-register increment (#380), combined with that qualified source, additionally passed 57 tests / 2,026 assertions for incident lifecycle, every endpoint's role/school denial, timeline pagination, HQ roles/audit and table ownership; 14 route/access/tenancy tests / 2,318 assertions; Pint; both Larastan configurations; and route/config/view caching. Browser creation, reading and timeline writes passed at 390px and 1280px, including long unbroken titles, with zero page overflow and zero WCAG 2.1 AA axe violations.

Earlier local attempts are superseded, not counted as passes: the disposable database initially exhausted its temporary capacity; some financial fixtures rejected custom shard database names; portal tests reused a cached session store; and teaching inference failed under the heavily concurrent run. The final backend reruns above passed after isolation/setup corrections and lower concurrency, with the production inference deadline unchanged. The upload browser fixture is synthetic protocol coverage, not real malware-detection qualification.

## Remaining hosted checks

GitHub Actions workflows remain pending/queued without assigned runners. Neither queued checks nor author-reported runs are represented as our completed hosted checks. The temporary runner probe was removed. No production release, new infrastructure or billing change is part of this merge qualification.

The formal Security Diff Scan could not be initialized because its referenced preflight resource and scan APIs are unavailable in this host. Direct review and repository security tests were performed; no formal or exhaustive scan report is claimed.
