# Cards: identity, online verification and bounded offline scanning

Use school-scoped cards with person/type/status, encrypted chip identifiers,
issuance/freeze/replacement history and explicit permissions for issuance,
encoding preparation and scanning. Fresh guardian ownership permits viewing or
freezing a child's card; learners view/freeze their own approved card; each staff
member can view/freeze their own card. Outside providers gain no school-wide
scanner or issuance permission. Old memberships, guardian links and person IDs
never grant access without the current role and active source relationship.
Replacement is atomic: retain/freeze the old identity and issue a distinct card.

Signed QR credentials use an asymmetric signature, short expiry, school/card/key
identity and unpredictable nonce. Rotation retains only the public verification
material needed for the bounded credential lifetime; secret signing material is
encrypted server-side and never enters a scanner/app/export. An online QR scan
requires fresh scanner authority, signature/expiry, current person/card status
and atomic nonce consumption. A repeated credential is refused. Logs contain the
minimum authorized card/person/scanner reference and outcome, with no credential
or cryptographic secret. A replay must not become a successful sign-in.

Offline scanning uses a device-bound, encrypted, authorized snapshot of only
permitted card/photo/status entries and public verification keys. The snapshot
expires within one minute; offline credential nonces are consumed locally and
pending minimal logs reconcile idempotently. Reconnection refreshes revocations.
After the bounded offline validity expires the scanner refuses and asks to
reconnect; it never fabricates an up-to-date frozen status. Independent offline
devices cannot prove global nonce consumption until synchronization, so tests
and docs explicitly separate local replay protection from online global replay.
Real gate speed, device/key protection and printer/chip qualification remain
external evidence; do not claim them from synthetic browser fixtures.

NTAG 424 DNA SUN is verified server-side using the official NXP AN12196
specification. Resolve the configured key version and encrypted PICC metadata,
verify UID/counter-bound session MAC, constant-time compare the truncated MAC,
and atomically reject old/replayed counters. A frozen/replaced card is refused
before a successful scan or sign-in. Validate the AES-CMAC primitive against
RFC4493 vectors and complete SUN examples against official NXP vectors. Key
rotation keeps historical verification keys only for the documented transition;
encoding exports contain no unencrypted master keys. Preparation creates an
authorized encoding plan/print export; no card order, paid provisioning or claim
of a real encoded/tapped chip is made by this development task.

Printer exports use CR80 layouts and explicit fields with private photo delivery,
never a public photo bucket. Digital card views show the current status and short
credential expiry; freeze asks for a reason and erases the visible credential.
Scanner photo bytes use existing private delivery/sanitization rules and avoid
the global Flutter ImageCache.

Foundation Phase tablet entry uses a dedicated restricted classroom guard and
restricted token storage. A valid classroom device registration and freshly
teacher-authorized learner roster precede QR entry. The result is never a normal
Student Sanctum token: v1/general API routes must refuse it. Bound session APIs
allow only the approved classroom features; card freeze, account approval or
teacher/device withdrawal revokes the session. Payment is outside Phase4.

Implement migrations/domain/cryptographic vectors and boundary tests first;
then additive API/SDK, staff panel, family digital cards and facilitator scanner
workflows. Cover wrong school/role/owner/key/card, expired/rotated/replayed QR,
SUN MAC/counter tampering, freeze/replacement, offline expiry, private photo
withdrawal, restricted-session refusal on v1, exports and actual app/panel flows.

Implementation contract: QR credentials use the `NEQ1` Ed25519 envelope, with a
25-second lifetime, school/card/revision/key identities and a random 128-bit
nonce. A fresh current source check follows signature verification. Gate and
encoder/classroom devices use distinct opaque credentials and capabilities;
ordinary account tokens cannot become device credentials. Initial portraits are
private sanitized pixels, not a public legacy avatar URL. Every explicit UTC
instant uses the shared UtcInstant cast.

Crypto references: RFC 4493 section 4; NXP AN12196 Rev. 2.0 (4 March 2025),
tables 1, 4 and 5 (https://www.nxp.com/docs/en/application-note/AN12196.pdf).
The first supported SDM encoding profile mirrors encrypted seven-byte UID and
24-bit counter and uses the documented empty MAC-input offsets. The verifier
also has an independent nonempty-input published vector. No factory key is used
for issued cards; production card keys are independently random and encrypted.

Current delivery: the first domain/cryptography increment implements issuance,
private portraits, short digital credential signing/key rotation, freeze and
replacement plus the published cryptographic vectors. Reader scan execution,
encoding, offline snapshots, classroom entry and UI/API are planned above and
remain unimplemented in this increment; see the card-domain self-test evidence.
