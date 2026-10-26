# Generic profile preview foreground retirement

The pre-fix default `ProfileScreen.withRepository` path pushed an authenticated
1×1 document image. Refreshing the real context with a withdrawn Parent role
left that `RawImage` visible. The regression failed on that retained image,
rather than a synthetic renderer flag or a changed harness timeout.

The default profile wrapper now pins an available context owner and passes a
one-way lease into its existing pushed file reader. Any context refresh or owner
replacement clears owned pixels/bytes immediately, rejects late bytes, and
retires the old form and pushed routes. Reopening derives fresh subject choices;
old child labels/drafts are not rebuilt under the new context. Loading followed
by an equivalent payload still retires the old lease. Standalone repository
consumers without a context provider keep their existing behavior and remain
responsible for their own authority lifecycle.

Attachment writes already reload and reauthorize their profile response; they
now keep the unrelated draft without invoking a global identity/context refresh.
Full field saves still invoke that callback and retire the old editor after the
acknowledged save. Their success message remains available.

Verification: 21/21 tests pass (seven new actual default-wrapper cases plus all
14 existing Profile cases), including account, school, role, guardian-source,
equivalent-payload and in-flight retirement, decoded-image disposal, byte
zeroization, and attachment/full-save callback behavior. Changed profile source
and tests analyze with no issues. No new browser/device qualification is inferred
from these native widget tests; the existing authenticated app browser target
remains part of CI.
