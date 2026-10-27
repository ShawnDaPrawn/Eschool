# Scheduled fee reminder verification

Local PHP8.3/MySQL qualification passed **16 tests /94 assertions**. It covers
explicit-off defaults, school calendar/hour/minimum-age/cadence boundaries, due
and future shares, split-holder/guardian visibility, actual foreign notice/owner
references, cleared debt, central preferences, disabled/read-only fees, prepared
inbox authority changes, expiry, one-time immutable binding, retention preserving
cadence, populated rollback refusal, actor/school form binding, revisions and UUID
replay, and atomic late-notice failure with safe retry. No financial posting is
created by reminder generation.

The first harness run used a shared Composer loader pointing at the preceding
worktree. Local dependency metadata is now isolated so qualification executes this
source. Initial fixture expectations used the wrong outbox table and share key;
they were corrected to actual domain_events/amount_cents without weakening checks.

Final browser, static, regression and hosted evidence are recorded on the feature
PR. Fluent translations, physical device/staging and actual transport acceptance
remain separate release evidence; gateways and provider activation stay deferred.

Finance browser checks passed **4 cases**, zero automatic retries: schedule
save/replay at390/820/1280px, two eligible parents with no duplicate prompts,
unchanged financial history, accessible layouts, overflow/duplicate-ID checks,
private response headers and console/HTTP assertions, plus Teacher refusal.
Baseline-free Money level5 analysis and browser TypeScript compilation passed.
