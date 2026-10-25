# Principal dashboard self-test

Twelve dashboard/tenant-schema/actual-migration tests pass with778 assertions.
They cover canonical precedence and half days, holiday exclusion, dated physical
enrolment, other-school observations, actual authenticated API observation,
guardian approval and withdrawal, duplicate daily receipts, current principal
and module withdrawal, pending-password denial, platform exclusion, bounded
periods, distinct unavailable/zero metrics and ninety-day observation retention.
Full configured Larastan and Pint pass. The final nine-test/43-assertion cohort also verifies school-admin access without a principal appointment and keeps appointment withdrawal covered with a Teacher principal.

Actual Playwright passed four tests in13.8seconds: principal dashboards at390,
1024 and1280 pixels and ordinary Teacher403. Period filtering, unavailable-source
labels, serious/critical axe, horizontal overflow, duplicate control IDs and
own-origin console/network checks pass. Fixtures are synthetic. English,
Afrikaans and isiZulu labels are authored with fallback catalogue keys; fluent
review remains external acceptance. No real learner data, production activation
or provider call was used.

Academic-risk and discipline counts plug into LeadershipSignals after those
Phase3 domains land. Financial collection remains unavailable until its Phase5
source is connected. These unknown sources are not reported as zero. This
record does not claim those unfinished integrations or all of Phase3 complete.
