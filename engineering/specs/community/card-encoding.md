# NTAG 424 DNA personalization

Use a separately registered school encoder and an explicit `cards-encode`
permission in addition to current card/reader management. An encoder credential
cannot be a gate reader, classroom token or ordinary account. Resolve the school
only from a verified delegated credential and reject mismatching context headers.
Preparing and downloading keys requires writable cards and fresh delegating staff
permissions. Parent/Student/Facilitator-only identities never become encoders through retained
links or permission records. Impersonation and platform staff remain excluded.

Prepare one immutable UID-bound plan per card. Generate five independent random
AES keys; encrypt keys at rest and seal the bounded job to the registered device's
X25519 public key. No master key or plaintext plan is printed, exported to the
browser, placed in a URL or included in logs. Retrying preparation resumes the
same plan rather than issuing different keys after an uncertain physical write.
The private workbench displays only references and status; its fields retire on
background, failed fresh source checks and a bounded foreground lease.

The local Python utility uses password-encrypted owner-only private-key files,
TTY secret entry and a device-encrypted durable confirmation outbox. It implements
PCSC and NTAG EV2 secure messaging against NXP AN12196, verifies chip identity,
uses authenticated FULL writes to file 2, applies the fixed SDM profile and rotates
keys 1–4 before key 0. It authenticates the new keys and checks exact file settings
before submitting a fresh SUN proof. Recovery first recognizes already-applied
keys and never blindly repeats writes after an unknown server outcome. A confirmed
plan permits verification only. The runtime overwrites all 256 bytes of the NDEF
file and uses one coherent read for the final mirrored proof.

The NDEF URI keeps SUN data in a fragment, with school/card references and encrypted
PICC metadata plus empty-input SDM MAC. The landing page performs no login or
collection. Metadata/key 2 and file-read/key 1 match the existing published-vector
SUN verifier. Only the registered encoder can confirm its own plan, and current
card, revision, identity/approval, issuer and preparation authority are rechecked
before confirmation and replay. SUN verification precedes `encoded_at`. An
unconfirmed or failed personalization can never pass the online gate verifier.
A fresh verification of an already-confirmed plan consumes its new counter but
never creates another encoded event or a gate scan. Freeze/replacement or a
changed source retires old job authority. A frozen unconfirmed chip must be
replaced rather than bypassing the recovered-chip proof requirement.

Use the existing encrypted card profile for the immutable job; no duplicate person
or card ledger is introduced. The schema change adds only the explicit permission
and leaves key material intact during rollback. System administrator defaults are
updated idempotently; school-created roles retain their own permission decisions.

Qualify real PHP↔Python envelopes, official authentication/secure-command/key-change
vectors, a stateful independent chip emulator, tampering and interrupted writes,
exact confirmation recovery, owner/school/role/device/module refusals, private
validation and workbench behavior. Physical PCSC readers/chips, target OS key
protection and actual gate timing remain distinct acceptance evidence; automated
emulation never counts as a performed chip write.
