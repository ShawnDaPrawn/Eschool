# Submitted private request recovery

PrivatePendingIntent retains one submitted exact JSON request in memory, scoped
to its account and school, using the existing Hive AES-CBC implementation with
an independent HMAC key. Nothing is written to a box, filesystem or preferences.
The JSON limit is two MiB; expiry is fifteen minutes and reads never extend it.
Explicit close or expiry clears the retained byte arrays and timer. This is not
a claim of forensic erasure of garbage-collected language/runtime copies.

Callers must freshly authorize the original source before opening/retrying the
request. Retain only already approved cropped derivatives, never an original
camera frame. A status404 can race an in-flight commit: it never authorizes a
replacement UUID. Keep the opaque locator even when the encrypted body expires;
recovery and unknown-result UI remain the feature caller's responsibility.

Three focused tests pass: original body independence and fresh scope, no TTL
extension, explicit close without reactivation, and oversized-input refusal.
No persistent offline or cross-process crash recovery is claimed.
