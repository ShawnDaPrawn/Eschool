# Online card reader domain

Gate and encoder devices receive distinct school-bound credentials with a stored
hash, bounded expiry, explicit kind and current issuer authority. These are not
user/Sanctum tokens. Every scan and replay rechecks the device, school, issuer,
module and current card/person source before returning a private receipt.

QR scans verify Ed25519 proof/key lifetime/card revision, then consume the nonce
inside the same locked transaction as the minimal accepted scan log and encrypted
operation receipt. A new operation/device cannot reuse the proof. SUN scans
validate the configured encrypted PICC/MAC and atomically advance the read counter.
Exact transport retries return the original scan rather than a second entry;
expiry permits only that original accepted receipt, while freeze or revocation
still denies it. Outer rollback consumes neither proof nor receipt.

Private sanitized portrait bytes are available only to the same device for its
own accepted scan within one minute, after fresh card/person authority checks.
They do not use a public image path or contain secrets in scan logs.

Validation: 30 tests, 84 assertions across the card domain and published crypto
vectors, using real MySQL transactions, private encrypted image storage and a
synthetic upload scanner. Includes cross-device nonce replay, SUN MAC tampering
and counter replay, rollback, expiry, wrong device kind/school, revoked issuer,
frozen card and current portrait-source withdrawal. Whole-app Larastan and Pint
pass. No real reader, chip encoding, offline scanner, classroom session, HTTP
adapter or app/panel workflow is qualified by this backend increment.
