# Private activity material document consumers

Implementation includes native inline readers and secure picker/upload/removal
flows, plus a staff panel page for upload, authenticated download and removal.
The existing generic profile attachment reader shares the owned renderer without
changing its route or draft semantics. Every offered app/panel catalogue receives
an explicit fallback for new labels, preserving existing case-folded translations.

Recorded checks so far:

- Existing groups, new file privacy/widgets/repository and affected family form
  cases: 58 passed. These include preflight erasure, late binary retirement,
  same-route account replacement and stale picker cleanup. The initial removal
  harness was corrected to render inactive before paused stops Flutter frames.
- File domain/API/panel: 22 tests / 197 assertions passed, including readonly
  removal, forbidden readonly upload, session-flash prevention, scoped download,
  exact removal replay and withdrawn staff access.
- Whole-app PHPStan passed with no errors; Pint passed for new/changed PHP.
- Bootstrap, design token and controller response guards: 5 / 14 passed.
- E2E TypeScript checks pass. Actual panel390/820/1280 workflows pass3/35.9s,
  including authenticated PDF bytes, explicit removal, axe, IDs and overflow.
  The local launcher was corrected to use public/ as its working directory;
  the required-field harness targets the exact labelled file input. Three
  actual app targets are registered in CI; Teacher, dedicated Facilitator and
  the family Student/Parent journeys pass with authenticated JPEG/PDF rendering,
  correct cookie/bearer account/school binding, owned-image disposal, explicit
  removal where permitted and private-content erasure on fresh access loss.
  The dedicated app keeps authBox unopened.
- Legacy profile regression passes14/14 from its own package directory. An
  attempted cross-package run used the wrong locale-fixture relative path;
  the corrected package run passes without changing those tests.

The first family Chrome tab terminated without a Dart assertion report; its
isolated repeat passes. Cgroup oom/oom_kill counters remain6/2 across that repeat;
no cause is asserted for the earlier tab crash. Browser transport uses the
explicit synthetic local scanner fixture, which is not malware detection.

This is not deployed scanner, private storage, iOS/device or OS picker hardware
qualification. Dedicated group conversations and consent notices remain retained
implementation follow-ons.


Readonly HTTP follow-up: a real API regression reproduced403 on authorized document
removal because the route's automatic module mode treated every POST as a new
write. The route now uses read-mode availability; the domain retains the fresh
writable guard for uploads. The full affected file+chat cohort passes34/282,
including removal200/private headers and upload403 with no additional scan.
