# Card workbench, CR80 export and online reader — 25 October 2026

This increment connects existing card services to the staff panel and a standalone
online reader. It does not implement offline scanning, physical card encoding or
restricted classroom sign-in.

## Automated evidence

- Real MySQL/HTTP/PDF cohort: 29 tests, 225 assertions. Covers issuance/portrait,
  freeze/replacement, wrong role/school/owner and device, expected-account refusal,
  private validation without session flash, both exact CR80 page dimensions and
  page count, printed-locator refusal, no cookie authority, read-only revocation,
  and authority withdrawn during PDF rendering.
- Reader registration through HTML string inputs reproduced Carbon's rejected
  string duration before the explicit integer cast; the regression then passed.
- Controller response, Bootstrap-free, design token and panel navigation guards:
  14 tests, 635 assertions. No new baseline/allowlist entry.
- Whole-app PHPStan: no errors. Pint, JavaScript syntax and TypeScript checks pass.
- Composer adds only BaconQrCode and its dasprid/enum dependency. Existing locked
  packages remain unchanged; SVG/PDF output is generated locally.

## Browser evidence

All three widths passed in 53.2 seconds. The final 390 px expiry/status
regression passed separately in 20.2 seconds.

Actual Chromium at 390, 820 and 1280 px exercises the private workbench, landscape
and portrait PDF download, reader registration/private pairing, printed-locator
refusal and real server Ed25519 proof verification/private JPEG delivery. The test
commits a real scan then loses its response and retries the exact same UUID; the
DB retains one scan. Admin cookies remain in the browser context but are omitted
from device requests, as are referrer headers. Teacher access is refused.

Accessibility, overflow, duplicate IDs and own-origin errors are checked. Portraits
are generated synthetic JPEGs, not real children's photos; the fixture supplies a
synthetic clean scanner result only during fixture creation. No product antivirus
bypass is installed. Foreground cleanup uses a controlled visibility-change event
because headless Chromium keeps tab visibility unchanged on bringToFront.

The final browser repeat also holds a real portrait response body while advancing
the controlled client clock. Expired pixels stay erased and the same operation
can recover once the connection succeeds. This proves the software timeout/lease
boundary, not physical camera/NFC or offline timing.

## Operational boundaries

Use a school-controlled HTTPS reader and a public encryption key generated on that
reader. A school operator with explicit reader permission registers it for at most
30 days; the private NED1 pairing credential appears only in the private response,
never a session flash or audit payload. Revocation immediately removes server
access. The scanner keeps its credential only in foreground memory. A reload,
background/pagehide, changed pairing or Clear reader requires pairing again.

Printed QR values are locators and cannot open a gate or create a learner session.
Physical card printers, camera/NFC hardware, encoded SUN chips, offline verification,
real gate speed, deployment and store/native release evidence remain pending.
