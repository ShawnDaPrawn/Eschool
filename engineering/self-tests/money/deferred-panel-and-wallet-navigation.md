# Deferred panel interactions and wallet navigation

Hosted CI exposed a parent wallet journey waiting for a lazily built statement
button before scrolling to its card, and clicks arriving before the deferred
pathway/photo handlers attached. Statement navigation now waits for the stable
wallet scroll container and scrolls to the exact wallet key. Script-only panel
controls start disabled and enable after their handlers attach, including newly
added pathway rows. Native form controls and saved data are retained.

Three complete pathway flows and three private-photo upload/tag/withdrawal flows
passed in Chromium at390/820/1280px. The tests deliberately hold the relevant
script response, assert controls are disabled, then release it and exercise the
real interaction. Accessibility, overflow, duplicate IDs (pathways), private
image response/cache headers and error checks remain active. The synthetic
INSTREAM fixture verifies upload protocol only; it is not malware qualification.
An initial local scanner outage and a subsequent disk-full cache write were
corrected in the test environment before the final passing photo run.

Compiled Learner and Parent Chrome journeys passed, including the exact child
wallet statement navigation after a parent transfer. Their current feature tree
also exercised storefront checkout and cancellation; hosted exact-source CI
remains required for integration. JavaScript syntax and e2e TypeScript passed.
No retries, longer timeouts or reduced assertions were introduced.
