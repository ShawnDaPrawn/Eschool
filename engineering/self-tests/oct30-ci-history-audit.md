# Eschool retrospective CI audit — 30 October 2026

## Result and scope

The audit covers 359 merged PRs, 2,424 historical workflow runs and all 232 failed workflow runs in that inventory. Failed runs contain 686 failed jobs; one invalid-workflow run has no jobs. Later runs for the active integration were checked separately.

**Current main is green:** commit `1da495d4361b7131c36c90e40c7204c0f61175c9` passed [CI, all 121 jobs](https://github.com/ShawnDaPrawn/Eschool/actions/runs/41012556555) and [the whole-history secret scan](https://github.com/ShawnDaPrawn/Eschool/actions/runs/41012557120).

352 merged PRs are represented in that main ancestry, through their source head or recorded merge commit. Nine older PRs have their recorded merge commit in main without the current source head; nine others have the source head without the intermediate-branch merge commit. Neither case should be mistaken for an undelivered feature.

Seven PRs (#374–#380) were merged into `integration/oct30`, and **have not reached main**. Their hosted checks are still pending. PR #373 holds the qualified integration; new PR #381 remains separate and unqualified in this audit.

## Per-PR evidence

[oct30-merged-pr-ci.csv](oct30-merged-pr-ci.csv) records all 359 PRs, exact source and merge SHAs, both ancestry checks, CI/Security results at those SHAs, and delivery state.

140 PRs have a successful CI run at their recorded source or merge SHA. The other 212 delivered main PRs rely on the later successful complete main build above. This is subsequent integration evidence, not a rewrite of their historical results. The remaining seven await main integration.

Exact source-head CI states are: 136 success, 96 failure, 114 cancelled, 7 missing, 5 queued and 1 pending. Missing, cancelled and queued results are not passes.

## Failed-workflow burn-down

[oct30-failed-workflows.csv](oct30-failed-workflows.csv) retains every failed run, commit, branch, job/failure counts and GitHub URL. Metadata for eight older runs required an unauthenticated public REST fallback after connector read timeouts.

583 of the 686 historical failed jobs have an identically named successful job in the current main CI run. The other 103 are:

| Historical failed jobs | Count | Subsequent evidence / disposition |
| --- | ---: | --- |
| Reserved facilitator/vendor identities | 24 | Both checks pass in [40962705005](https://github.com/ShawnDaPrawn/Eschool/actions/runs/40962705005), on an ancestor of main. |
| Whole-history secret scan | 21 | Successful current main Security run above. |
| Firebase documentation | 3 | [40962704996](https://github.com/ShawnDaPrawn/Eschool/actions/runs/40962704996) passes on an ancestor of main. The inspected failure rejected Chromium on Ubuntu 26.04; the workflow now explicitly selects supported Ubuntu 24.04. |
| Dependabot update service | 5 | **External failure remains recorded.** All five logs report “fetching job details: missing response”. A retry of 40381482620 returned GitHub 403, “This workflow run cannot be retried”. Current main dependency audits pass, but this does not establish that Dependabot updates are healthy. A repository owner can check the bot's update status in GitHub. |
| Web previews | 2 | Both preview jobs pass in [40257467766](https://github.com/ShawnDaPrawn/Eschool/actions/runs/40257467766), on an ancestor of main. |
| Former unflavoured Android smoke names | 45 | Replaced by the current family and teacher partitions plus explicit staff flavours. Both complete Android aggregate checks and all their parts pass in current main CI. |
| Unsigned iOS release builds | 2 | Both subsequently pass in [40061287512](https://github.com/ShawnDaPrawn/Eschool/actions/runs/40061287512). This historical release evidence is not a new iOS release qualification. |
| Wiki initial clone | 1 | Successful [40899698176](https://github.com/ShawnDaPrawn/Eschool/actions/runs/40899698176), on an ancestor of main. |

A later green execution establishes subsequent check coverage; it does not prove the root cause of every earlier intermittent failure. The initial invalid Firebase workflow is superseded by the working workflow and its subsequent successful executions.

## Recent failures examined directly

- The pre-main admin-page failure identified `saas/packages` returning 403. The latest main backend shards pass. HQ role separation and the new HQ pages also have dedicated integration coverage.
- An older backend failure expected 403 but received 422 in financial module controls. Later main PHP partitions pass; no financial-history deletion or weakened isolation was introduced for this audit.
- The main integration browser failure in [40968293577](https://github.com/ShawnDaPrawn/Eschool/actions/runs/40968293577) reached Chromium's error page while preparing academic reports. Subsequent complete main browser checks pass. The real report create/review/sign/PDF/release journeys at 1024px and 1280px, and conflicting-comment preservation, were additionally rerun locally: **3 tests passed, with retries disabled**. The first local attempt failed on a missing disposable storage symlink; after the same `storage:link` setup used in CI, all three passed. The old transport failure was not reproduced, so its root cause is not claimed as fixed.
- Corrections found in the current open queue are already published in PR #373. See [oct30-merge-qualification.md](oct30-merge-qualification.md) for the code changes, four backend partitions, Flutter/API tests, browser evidence and conditional integration skips.

## Remaining work

1. Obtain actual green hosted CI/Security/staff checks for the final PR #373 head before merging it into main with a merge commit. Pending checks do not establish that CI has passed.
2. Qualify PR #381 separately, fix any confirmed issues, and repeat the head/base checks before integration.
3. Check the Dependabot service failure through GitHub; its failed service runs cannot be retried with the available API.
4. Restore the execution workspace before further local fixes/tests. It became unavailable after the report-browser verification; GitHub reads and source publication still work.

No production deployment, paid infrastructure, payment-provider activation, weakened assertions or manufactured check statuses were used to clear this history.
