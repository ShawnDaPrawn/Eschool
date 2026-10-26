# Offline card identity: domain and cryptographic protocol

This increment has no public endpoint, scanner entry or login/collection authority.
It is a qualified component for the remaining offline reader workflow.

On 26 October 2026, 10 native unit/domain tests / 54 assertions passed. The combined offline + existing card domain, after inheriting the peer API,
passes 39 tests / 279 assertions. The
whole Laravel application passed PHPStan before the peer API merge; the peer
API itself was independently qualified. Four Node WebCrypto tests consume
actual PHP/Sodium-produced packets and QR signatures and pass. These execute
the browser-compatible module against an independent cryptographic runtime,
not a Chrome UI or physical reader.

The snapshot contains at most 100 current cards, 128px sanitized private
portraits bounded to 64 KiB each / 2 MiB encoded per page, and public signing
keys. X25519/HKDF-SHA256/AES-256-GCM binds school, device, snapshot and a
60-second interval. Ed25519 NEOS1 metadata is distinct from NEQ1 authentication.
A separately supplied authenticated server keyring is mandatory in the client
module; a cache's self-announced public key is not a trust anchor. The reader
private CryptoKey is nonextractable. The pre-download monotonic fetch anchor is
mandatory; callers must only open an authenticated fresh response or retain its
original live-page anchor. Browser-restart/cache hydration cannot reset the
lease and remains unsupported without that valid provenance. No SUN/card/master secret enters a snapshot.

Tests cover wrong reader/key/AAD/tag, altered signatures/references, local
instance replay, credential/snapshot expiry, monotonic retirement and byte
erasure, freeze and approval/issuer withdrawal, reconciliation idempotency,
wrong school/device and signature verification epochs. Reconciliation rechecks
current identity, consumes accepted nonces globally, and records history-only
`offline_accepted`, `status_changed`, `access_changed` or `duplicate` outcomes.
It explicitly returns collection_authorized=false and sign_in_authorized=false.
Existing online gate collection and portrait routes do not accept offline logs.

Still required software: authenticated delegated HTTP/SDK contracts, safe
reader key/cache storage, atomic durable per-device nonce claims across cache
reopens/tabs, encrypted pending-log synchronization, and actual bounded
photo/status/retirement UI. The cryptographic module's in-memory nonce set
alone is not the durable reader replay store. Encoding and restricted
Foundation classroom entry are separate retained work. Hardware/store/
production acceptance and a real subsecond gate measurement remain unrun.
