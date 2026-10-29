# Whole-app consolidation: session privacy and private invitations

Review findings F2–F4, owner accepted29 October2026. A response or cache write
from a replaced account/school must not restore that context or sign out its
replacement. Invitation credentials must not reach debug output. Current-session
401 handling and offline context within the same session remain supported.

The shared client pins each authenticated request's session identity; anonymous
calls cannot trigger sign-out. Both apps include credential generation, token,
school and an opaque browser-session marker. The server rotates the marker only
on web authentication transitions, with the session cookie's domain/path/Secure/
SameSite settings. It is readable for comparison and grants no authentication or
CSRF authority. Native bearer behavior is unchanged.

Context loads invalidate outstanding generations and serialize cache erasure
behind writes already underway. Identity changes cannot use the prior cache or
its freshness timestamp. The apps recognize replacement sign-ins even when both
states are Authenticated; context and child/school selections clear synchronously
before loading the replacement. Finishing persistence of an old selection clear
cannot overwrite the new selection. Invitation repositories mark both requests
private; central debug URL logging omits sensitive URL components and redacts
credential path segments.

Local verification29 October2026:

- Core:143 tests passed, including five HTTP paths with current/stale bearer and
  cookie401s, another portal replacing cookies without local generation changes,
  anonymous startup and private URL logging.
- Session:109 tests passed, including late success/failure/unsupported answers; held writes/clears; a failed
  school switch followed by restart; replacement refresh despite a fresh old
  timestamp; child/school clearing; cookie credential generation. Full-package
  results are recorded with this delivery.
- People:75 tests passed, including actual invitation fetch/accept paths using
  encoded synthetic credentials with no debug output.
- Family app:7 relevant tests passed, including real SessionWatcher/AuthCubit
  invitation replacement, child switching and offline sign-out. Teacher offline
  sign-out passed.
- Backend:48 tests /408 assertions passed across real-CSRF portal sign-in,
  cross-portal marker rotation, unchanged reads/current401, native tokens,
  guardian approval and context authorization/isolation. PHPStan and changed-file
  Pint passed. Family analysis has no errors or warnings.

An independent investigation and one fresh candidate review were performed. The
review found the signed-in invitation path, persisted cache after replacement
and cross-tab identity gap; each is addressed by a regression above. This is
targeted fix verification, not an exhaustive audit. Hosted CI/review and main
integration remain separate until recorded. No production/device testing claimed.

Cross-subdomain comparison depends on the existing shared session-domain setting.
The marker does not replace Laravel authentication/CSRF or claim to solve all
server Set-Cookie ordering races. Existing browser sessions obtain it on their
next sign-in.
