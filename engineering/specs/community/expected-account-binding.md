# Expected account binding for private app requests

A school ID and local token check do not detect a cookie account replaced in
another browser tab before the app refreshes its context. A stale private view
must not process a different account's response or attribute a write to it.

Authenticated clients may send X-Account-Id with the account that owns the view.
The server compares its canonical string to the authenticated user's identifier
before choosing a school, reading model data or replaying an operation. A mismatch
returns 403 with no private payload. The header never authenticates anyone or
grants a role, relationship or permission. Existing clients may omit it; fresh
server authorization still applies. Public unauthenticated operations do not use
an expected account.

ApiClient.send exposes the optional accountId argument. Updated activity and
message repositories pass their pinned account. Cover/mastery consumers add the
same argument when adopting the shared change. OpenAPI documents the optional
ExpectedAccount parameter on all current authenticated operations, and the
pinned SDK exposes the optional X-Account-Id argument without changing any model
or existing required argument. The cover configuration's school-year query also
becomes optional with the current-year server default already implemented by its
owner; response shape is unchanged.

Test cookie-account replacement, positive binding and legacy omission, malformed
values, unauthenticated spoofing and missing role authority. Verify stale writes
are refused before validation or receipt lookup, and verify updated clients send
the header while unauthenticated transport omits it. Compare prior paths/schemas
and regenerate the SDK once against the combined activity/mastery/cover contract.
