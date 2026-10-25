# Private incident-photo HTTP adapters

The public phone follow-up now offers a JPEG/PNG file/camera input after the
report exists, with identifying-pixel guidance, inline sanitized thumbnails and
code-in-body downloads. Scanner refusal/outage preserves the authorized report
and code, states that the photo was not received, and never flashes the file or
private fields to a session. Public requests do not use ambient account identity,
create cookies or use browser storage. CSP permits bounded inline images only.

Authenticated owner endpoints require current ownership and fresh learner or
guardian authority. Staff app and panel reads require separate current incident
team authority, including binary downloads. Panel download links pin the displayed
account and school as well as rechecking the current actor's report scope. A case
assignment, administrative configuration permission or platform identity grants
no report image access. Read-only mode retains reads and refuses uploads.

Five additive OpenAPI operations describe multipart uploads and private JPEG
responses. Optional configuration/photo fields preserve older clients and allow
new controls to remain hidden against older servers. No private code enters a
path or query. Errors and success carry no-store/private and no-referrer headers.
The inherited domain review fixes retain anonymous audit privacy and correctly
orient all eight EXIF transformations.

Evidence so far: 25 real MySQL/domain/HTTP/panel tests with 742 assertions,
Pint and whole-app Larastan pass; all 368 global contract scenarios passed. The
contract check first found two test-fixture issues (a numeric placeholder for a
UUID route, and a deliberately unregistered GET sent through the POST contract
validator); the corrected affected cohort passes. Final combined contract/SDK
and actual phone-width photo browser checks are recorded below after execution.

Reproduce browser transport using the isolated synthetic demo DB, the dedicated
`e2e/tests/incident-photos.spec.ts`, and the existing public-report fixture. For
that test only, run `e2e/support/synthetic-photo-scanner.cjs` with
`ALLOW_SYNTHETIC_PHOTO_SCANNER=1`, and point the ephemeral backend's
`CONTENT_CLAMAV_HOST`/`CONTENT_CLAMAV_PORT` at it. The fixture defaults to
loopback; a private container bridge address may be explicitly selected with
`SYNTHETIC_PHOTO_SCANNER_HOST`. Never configure this fixture for a deployment.
It proves multipart/protocol/browser handling, not malware-engine detection.
Live scanner definitions, camera/device rendering, ingress size/time limits and
production load remain separate qualification work. App upload/decoded-image
controls follow this HTTP adapter; no app-photo completion is claimed here.

Final combined verification: 421 real MySQL/domain/HTTP/contract cases with
2,859 assertions pass, including all v2 routes on the inherited discipline and
recognition contract. Whole-app Larastan, Pint, strict OpenAPI lint and browser
TypeScript pass. The pinned client generation produces 1,529 files and all
95 SDK tests pass. SDK analysis reports zero errors/warnings with 845 generated
style infos. The generator's inline array-enum annotation bug was avoided with
an equally restrictive MIME string pattern; no generated source was hand-edited.

Actual Chromium browser: 390 and 1280 px photo upload, sanitized 80×40 preview
and POST attachment download pass (2 tests / 4.9 seconds), including axe, unique
IDs, horizontal overflow, same-origin requests and absence of codes in URLs,
cookies or browser storage. The scanner was the explicit synthetic protocol
fixture bound to the private container bridge; no live AV or physical camera
qualification is claimed. Its process and the ephemeral web container stopped
after verification.

The combined SDK also carries requested optional/no-default `discipline`,
`house_points`, `awards` staff flags and Risk/Discipline `writable` metadata.
Their owning streams implement fresh server capability values and actual cards
separately; absence grants nothing and preserves older server responses.
