# Offline identity reader: delegated API and browser workflow

Implemented on 26 October 2026 as a follow-on to the offline domain/protocol
component. This is an identity reader, with no collection, login or payment grant.

The delegated GET prepares an encrypted roster for a fresh random request UUID;
that UUID is also signed inside NEOS1. The browser accepts only its current
HTTPS response and retains the original pre-download monotonic anchor. It never
restores a roster with a new lease after reload. Signed whole-second time has an
explicit uncertainty bound: expiry uses the conservative upper estimate, while
reconciliation uses the receipt-based lower estimate and the signed proof's issue
time. A fresh proof just across a second boundary is accepted without producing
future scan timestamps. The roster expires conservatively within sixty seconds.

The scanner is `/card-reader/offline`. Device X25519 private keys are
nonextractable CryptoKeys in IndexedDB; only their public key is shown for
registration. Reader credentials remain in foreground memory. No portrait, roster,
manifest, proof or device credential is stored unencrypted. A nonce claim and the
X25519/HKDF/AES-GCM encrypted pending operation enter one IndexedDB transaction
before pixels are shown. Two tabs and a reload cannot consume the same local
proof again. A lost response retains the exact original encrypted operation UUID;
reconciliation creates one history row and never turns that row into an online
portrait, collection pass or sign-in. Cross-device global replay is decided by
the server on reconciliation, rather than invented while offline.

Portraits have a separate fifteen-second display lease, with download/decode time
included, and are cleared on expiry, source retirement, backgrounding, pagehide
and bfcache restoration. Clearing the reader erases the credential and visible
bytes while retaining only encrypted pending history for authorized reconnection.
The old online reader's no-script form fallback was also corrected: secret
controls have no names and forms use POST, so a failed script cannot put delegated
credentials or card proofs into a GET URL.

Evidence:

- Initial full offline/card native cohort: **41 tests / 357 assertions**.
- **Seven** Node/WebCrypto cases using real PHP/Sodium packets, including altered
  signatures/AAD/device/key, expiry and clock rollback, signed challenge
  substitution, integer-second uncertainty and encrypted exact-operation retries.
- Actual Chromium at **390, 820 and 1280px**: register a real browser public key,
  fetch the real Laravel encrypted roster, disconnect the network, display the
  private sanitized portrait, refuse replay, reload/restore the nonextractable key,
  synchronize the pending operation and refuse its durable replay. A genuinely
  committed but dropped response is retried with one UUID and produces one row.
  Accessibility, duplicate IDs, overflow and foreground pixel/credential erasure
  checks pass. The phone case additionally advances the monotonic clock and proves
  actual cache/pixel retirement. Local photo-display checks are below two seconds;
  this is not a physical gate or device timing qualification.
- Actual IndexedDB two-handle race: exactly one nonce/operation transaction wins;
  stored payloads are encrypted, the key remains nonextractable after reopen,
  cross-school rebinding and durable nonce replay are refused.
- Actual JavaScript-disabled online-reader form: the private input is absent from
  both URL and submitted body. No user session is used by the device transport.
- **Five browser cases pass in 24.5 seconds**. Final challenge HTTP cases pass
  **2 / 84 assertions**; the combined native cards, Foundation, complete OpenAPI
  scenario catalogue and role matrix pass **674 / 3,264 assertions**.
- After enabling typed response validation for the actual Foundation requests,
  **15 / 130 assertions** pass, including a fresh sequence through all twelve
  success operations. The focused isolated route walk passes **16 / 128**; whole
  source response/M3/token guards pass **5 / 14**. All nine ordinary account roles
  are refused on every restricted tablet/learner operation.
- TypeScript, changed-file Pint and selected card PHPStan pass. The Root-owned
  Foundation service/controller static checks were independently qualified.
- Offline two operations/seven schemas are folded with Foundation twelve
  operations/twenty-nine schemas and its two dedicated authentication schemes.
  All prior **465 paths / 1,185 schemas** remain structurally unchanged. Both
  pinned SDK generations match **2,511 source/manifest hashes** (2,509 Dart files).
  All **178 SDK tests** pass, including exact encrypted-operation body placement,
  fresh challenge/null cursor, integer epochs, explicit restricted capabilities,
  and refusal to borrow ordinary bearer configuration for learner sessions. SDK
  analysis has zero errors/warnings (1,340 generator style infos); strict OpenAPI
  lint passes.

Hardware/PCSC key protection, real cameras/chips, iOS/Android secure storage,
independent offline-device global replay, production TLS/recovery and physical
subsecond gate measurements remain separate acceptance evidence. NTAG encoding
runtime and the restricted Foundation classroom implementation are separate
retained software slices, not dismissed as hardware acceptance.
