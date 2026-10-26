# NTAG encoding software: 26 October 2026

Implemented the authorized school workbench, encrypted UID-bound preparation,
separate encoder transport, local PCSC/EV2 utility and fresh SUN confirmation.
This is software qualification; no physical reader/card was opened or written.

`cards-encode` is explicit alongside card and reader management. System defaults
receive the permission idempotently; school-created role decisions are preserved.
Parent/Student/Facilitator-only profiles cannot inherit encoder authority from
retained staff records. Both the device issuer and original preparer are checked
freshly, including after writes and before replay. The workbench binds its
account/school before inputs and clears its private view on background, expired
lease or failed source refresh. Its source ZIP contains only README, pinned
requirements and non-symlink runtime Python files, with no tests, key files,
receipts or cache artifacts.

Preparation retains one encrypted five-key plan per card/UID. Different requests
cannot silently replace keys after an uncertain physical write. The device-bound
X25519/HKDF/AES-GCM envelope has a separate purpose and ten-minute authorization
window. All five issued keys are independent random nonzero AES values. No
plaintext chip key appears in the workbench, API, audit, URL or source download.
Only a correctly bound SUN UID/counter/MAC can set `encoded_at`; unconfirmed
profiles cannot pass the gate. Exact confirmation replay produces one encoding
event; a later fresh verification can advance the counter without another event,
card issuance, gate scan, sign-in or collection outcome.

The local utility implements bounded PCSC frames, the documented ordinary NTAG
424 family guard, EV2 authentication and secure messaging, full NDEF writes,
fixed SDM offsets and key0-last rotation. It verifies the new keys/settings and a
coherent final proof. Password-encrypted owner-only PKCS8 keys and an encrypted
exact confirmation outbox support uncertain response/restart recovery. Both
monotonic and Unix expiry are checked before APDUs. No production/factory key is
used as an issued key. [Runtime evidence](../../admin-panel/tool/cards/encoder/TESTING.md)
records the precise protocol and filesystem boundary tests.

Verification completed:

- **700 native tests / 3,888 assertions**: cards/offline/Foundation, complete
  OpenAPI catalogue/refusals, all account roles and full migration rehearsal.
- Final **19 cases / 173 assertions**: fourteen encoding lifecycle/HTTP/workbench
  cases plus response/Material/token source guards. Includes first valid counter0,
  both independent delegators' withdrawal, readonly module, freeze/restore revision,
  guardian approval withdrawal, wrong school/device/owner, unknown/query input,
  unconfirmed gate refusal, exact retries and a permission change during creation
  rolling back all key material. The initial converted-role fixture lacked an
  explicit school ID; the corrected real MySQL fixture passes.
- **18 dedicated route checks / 144 assertions** refuse anonymous, other-school
  and normal user credentials before restricted device data.
- **46 Python cases**, no skips: official AN12196 vectors; independent stateful
  emulator; interrupted writes/key changes and exact CLI recovery; real encrypted
  local files/permissions; bounded HTTP/redirect behavior; actual PHP encryption
  opened by Python; a completed Python personalization proof accepted by the real
  PHP SUN verifier. A real counter0 decoder regression first failed, then passed
  with boolean/string/negative/overflow refusals retained.
- Actual Chromium **390/820/1280** journeys pass **3 / 37.8s**: download/check the
  real ZIP, prepare the real immutable job, decrypt its actual envelope, execute
  the Python personalizer against the independent emulator, confirm its proof
  against the real Laravel endpoint, retry the original operation, see verified
  status, then withdraw permission and prove DOM retirement. Axe, duplicate IDs
  and overflow checks pass. The initial browser exposed missing asset wiring;
  the deployed template sections were corrected. A real code contrast violation
  was fixed without an accessibility exemption. The reserved `card-` CSS class
  prefix was renamed rather than added to the Bootstrap baseline.
- Two pinned SDK generations match **2,523 source/manifest hashes** (2,521 Dart
  files). All **182 SDK tests** pass, including four new wire/purpose/counter0
  cases; analysis has zero errors/warnings (1,342 generated-style infos).
- Strict OpenAPI lint, full Pint, selected changed-source PHPStan, TypeScript,
  JavaScript syntax, actionlint and staged secret checks pass. The required full
  integration/hosted CI remains the merge authority; a selected static pass does
  not claim a new whole-repository static run.

A SUN proof shows possession of configured keys and a UID/counter, rather than
hardware attestation: an authorized encoder necessarily holds those keys. Real
chips/readers, factory-personalization behavior, target drivers, Windows ABI/ACL,
OS key protection, real subsecond gate performance and deployment require their
own acceptance. macOS is explicitly refused pending ABI qualification. No live
hardware, card order, paid service, wallet/payment or production activation was
performed. These limits are recorded separately from the implemented workflow.
