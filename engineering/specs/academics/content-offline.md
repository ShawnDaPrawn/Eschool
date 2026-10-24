# Encrypted offline learning material

Complete the Phase 2 content-cache requirement with explicit saved copies, not a
generic response cache. Only a currently authorized online reader can be saved.
Store Markdown in encrypted Hive with a random platform-secured key; bind every
entry to account, school, learner and immutable published version/checksum.

Saved copies expire within the server's one-day lease and the original local
request deadline, whichever is sooner. Clock rollback, corrupt/mismatched data,
expiry, logout and school/account replacement invalidate them. Keep at most 20
copies per school/account; a reader may remove its saved copy. Offline reads do
not claim server read acknowledgements. Hide content on inactivity and enforce
expiry while visible as well as on reopening.

Try fresh authority first on open/resume. A bounded private batch endpoint checks
up to 20 saved version IDs against the same live policy as the reader, without
returning bodies or revealing inaccessible IDs. Successful checks remove missing
versions and refresh leases only for immutable authorized versions. Transport
failure can use an unexpired copy; HTTP refusal, server failure, certificate
failure, cancellation or malformed responses cannot be treated as offline access.

The school/child switcher still owns navigation. Explicit copies for another
child at the same school remain encrypted and cannot appear in the selected
child's library. Root session listeners purge the key on school/account change,
even when a content route is closed; serialized generation checks reject late
prior-context writes. Cold start with a different scope erases the old key.

Cover real encrypted-store restart/plaintext/erase behaviour, deadlines and clock
rollback, wrong person/school/account, revocation, transient transport fallback,
late reads/writes, reader expiry, phone/desktop widgets and the app Chrome flow.
Actual disconnected-device revocation cannot be immediate; the bounded lease is
the documented limit. External pilot/device/security acceptance remains separate.
