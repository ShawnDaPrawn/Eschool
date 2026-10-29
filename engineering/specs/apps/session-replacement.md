# Account and school replacement boundaries

Owner accepted whole-app review findings F2–F4 on29 October2026.

After sign-out, account replacement or a school switch, outstanding context
requests cannot publish, keep or erase a replacement context. Cache erasure must
follow any old write already in progress, including when the new fetch fails.
Same-session offline context remains available. Replacing an authenticated account
through a guardian invitation clears its prior children and school immediately.

An unauthorized authenticated response may sign out only the session for which
it was sent. Compare bearer credentials/local generation and, in cookie mode,
the server's shared non-authorizing browser marker. Anonymous requests never
sign out the current user. Preserve existing current-session401 errors, headers,
CSRF, role/module/tenant enforcement and native token contracts.

Invitation fetch and acceptance are private requests. Debug output omits URL
credentials, query/fragment values and path tokens, without changing encoding,
request bodies, errors or invitation acceptance behavior.

Verification: engineering/self-tests/whole-app-session-security.md. No gateway,
Phase6, production or spending change. Cross-domain browser comparison requires
the existing shared session domain; existing browser sessions receive their
marker on their next sign-in.

The initial resolution of an unspecified school to the school returned by the
successful context is the same session. It must retain fresh/offline context and
downloads. Only this monotonic resolution may normalize identity, and only when
credentials and the browser marker still match; actual school switches, new
credentials and browser replacements continue to invalidate the old context.
