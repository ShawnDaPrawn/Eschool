# Combined browser qualification corrections

The combined CI Playwright job exposed two qualification issues. Admissions
fixtures correctly refused arbitrary databases but also refused CI's reserved
`eschool_e2e` database. The shared guard now permits exactly that name only in
local/testing with CI enabled, alongside local `*_test` databases. Production
remains refused regardless of database name or CI flag; a pure regression checks
these boundaries.

The report workflow read `batch_id` immediately after clicking Prepare while the
asynchronous POST was still pending. Both failed traces show the prepare request
aborted when the premature zero-ID assertion tore the page down, with no response
or redirect completed. The test now waits for the positive batch-ID URL before
retaining the existing positive-ID, comment, signature, PDF and release assertions.
Actual report and admissions workflows are qualified again before publication.

Fresh verification: the fixture guard passed1test/7assertions, selected Pint and
TypeScript passed, and all6 actual Chromium workflows passed (1.3minutes): reports
at1024/1280, a teacher's stale comment recovery, and admissions at390/820/1280.

The same integration pass also found finite route samples generating invalid
support-audience/transfer-kind/transfer-lane URLs, so route matrices were checking
router404s before reaching the existing authentication guards. Their samples now
use valid domain dimensions while all401/403/role expectations remain unchanged.
Recognition CSS now uses the declared Material3 colour tokens; recognition warning
and admissions hidden-label markup use the existing M3 classes rather than
Bootstrap aliases. No application permission or cache policy is broadened.

The corrected route samples, real401reachability, full wrong-profile role matrix,
production fixture guard and design-token scan passed14checks/40assertions. The
separate Discipline role-group registration and Risk M3 control corrections remain
in the parallel learning-stream CI patch; all-view and route scans run on their
combined descendant before merging main.
