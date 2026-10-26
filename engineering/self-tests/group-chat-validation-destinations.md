# Group conversation validation destinations

Scoped repair for PR294 review discussion r4376942041, based on integration
PR285 at `135cd7230f96297c870750f79541ac06dca711fa` (26 October 2026).
The current integration branch, open repairs and review replies were checked
before implementation; no concurrent repair was found. Before publication the
branch was fast-forwarded to `1b7127b4ba37b84a8d74b4ea6f13399300b37c91`,
an unrelated Exam Coach viewport repair; no chat source changed upstream.

The panel previously retained draft text in HTTP 422 responses but reset its
conversation type, audience and private reply destination. A reply draft also
appeared in the new-conversation form. Three synthetic HTTP regressions failed
against the integration source (8 cases, 74 assertions, 3 failures; the five
existing panel cases passed). No live exposure is established.

The repair keeps valid kind/audience and reply target/visibility alongside the
bounded draft, only in the originating form. The destination must still be
present in the freshly authorized listing/conversation. Invalid, withdrawn,
removed or off-page destinations discard the text and explain that a new draft
is needed. Existing server authorization runs again on resubmission. The create
form retains its operation UUID and opens on a retained validation draft.
No session flash, URL draft, API or database schema change is introduced.

Verification uses PHP 8.3.36 and the exact Composer lock, with separate local
`chat_validation_test` and `chat_browser_test` synthetic databases. The existing
app database is not used. Initial environment setup failures (archive endpoint
access, container parent-directory mount and a dependency replacement during
an early test) were corrected before the clean baseline and repaired runs.

- Clean baseline: `GroupChatPanelTest`, 8 tests / 74 assertions, three expected failures.
- Initial repaired panel: 8 tests / 144 assertions passed.
- Final expanded `GroupChatPanelTest`, `GroupChatApiTest`, `GroupChatDomainTest`,
  `GroupChatNoticesTest` cohort on CI-compatible MariaDB 10.11.19: all 35 tests /
  531 assertions passed in 45.3 seconds.
- The same cohort on the saved MySQL 8 server: 34 passed / 1 failed (519
  assertions). The unchanged notice test at `GroupChatNoticesTest.php:105`
  assumes JSON object key order; MySQL returns the same four keys reordered.
  That test calls the unchanged domain/outbox directly, bypassing the panel.
  It passes unchanged on MariaDB, the engine used by CI; no assertion was weakened.
- Whole-application PHPStan (`--memory-limit=2G --no-progress`): passed, zero errors.
- Whole-application Pint (`--test`): passed, 1,957 files.
- Playwright TypeScript (`tsc --noEmit`): passed.
- Extended actual Chromium conversation journeys (390/820/1280): all three passed
  in 1.5 minutes, including both 422/resubmission flows, private message reads,
  axe, duplicate IDs, overflow, expected-only HTTP errors and withdrawal erasure.
  Initial local proxy and missing storage-link setup failures were corrected;
  no application assertion was waived.
- UI guards (`BootstrapFreeTest`, `DesignTokensTest`): 2 tests / 3 assertions passed.
- Scoped diff gitleaks 8.30.1 (checksum-verified CI version) and whitespace checks: passed.

The regression reads actual rendered HTML controls (including browser-default
select behavior), corrects only the invalid field and resubmits. It checks
persisted privacy/audience and fresh family readers, invalid/withdrawn
selections, private reply ownership, removed/invalid targets, session privacy
and separation of the two forms. Existing scope tests retain wrong-role,
wrong-school, mismatched account and retired allocation coverage.

Hosted exact-head CI remains a separate verification step. The complete local
backend suite, other app/device profiles and staging/production acceptance are
not claimed. No merge, deployment, production-data access or meeting
idempotency change is part of this repair.

## Hosted catalogue correction

The first hosted run on `c494390820d9e3b067c2f113cac6f7e279288e13`
passed Security, panel quality/runtime and PHP shards 1–3. Shard 4 exposed the
new warning's missing Afrikaans/isiZulu catalogue entries (one failure and one
error in `SouthAfricanLanguagesTest`). The follow-up adds the same explicit
English fallback used by the surrounding untranslated chat labels, preserving
catalogue order and placeholders. It makes no controller or test changes and
does not claim fluent-language acceptance.

The exact affected `SouthAfricanLanguagesTest` and `TranslationsTest` now pass
locally on MariaDB: 7 tests / 45,333 assertions. Hosted verification must use
the updated follow-up head, not the superseded first run.
