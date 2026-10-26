# NavigateEd local NTAG 424 encoder

This operator tool obtains a device-bound, encrypted ten-minute encoding plan,
authenticates the intended chip, writes the approved NDEF profile with EV2
secure messaging, changes all five application keys, verifies a fresh SUN and
confirms it with the server. It does not create gate scans, authorize collection,
sign a learner in, or activate an account.

The protocol implementation is tested against NXP AN12196 Rev. 2.0 (4 March
2025), an independent stateful card emulator, and the actual PHP cipher. **No
physical reader/card, driver, Windows PC/SC ABI or factory-personalization
workflow has been qualified by this software test run.** Linux pcsc-lite and
Windows winscard adapters are implemented; macOS is explicitly refused pending
ABI qualification. Qualify the reader/card/driver combination with disposable
test cards before authorizing use on real cards. Do not treat an emulator pass
as a physical encoding pass.

## Install and register the encoder

Use Python 3.11 or later, an isolated virtual environment, and the pinned
dependency. Run from this directory; the downloaded workbench bundle preserves
the `nexencoder/` package tree. PC/SC is supplied by the host operating system
and reader driver; this tool does not download drivers or SDKs.

```sh
python3 -m venv .venv
. .venv/bin/activate
python3 -m pip install -r requirements.txt
python3 -m nexencoder --initialize-key --key-file ./encoder-private.pem
```

The tool asks twice for a hidden passphrase (at least twelve UTF-8 bytes), writes
an encrypted PKCS8 file with owner-only permissions, and prints **only the public
setup key**. Register that public key for a dedicated **encoder** device in the
authorized Cards workbench. Save the returned `NED1` access code securely. It
is entered at a hidden terminal prompt, never in a command argument, environment
variable, shell history, URL, cookie or log. Do not use a gate-reader credential.

The private key must be a regular, owner-only, singly linked file. Outbox
directories must be owner-only. On Windows, equivalent ACL and PC/SC deployment
qualification remain required; POSIX permission tests do not prove Windows
ACL enforcement. Do not redirect secret input through a pipe; hidden TTY input
is required. Python cannot guarantee zeroization of immutable memory or prevent
OS-level swap/core capture; use a trusted, appropriately protected workstation.

## Inspect and prepare

For an unpersonalized test card, read its authenticated UID with the existing
master key supplied by the operator:

```sh
python3 -m nexencoder --inspect-uid --execute-on-reader
```

This local command asks for the existing key0, authenticates EV2 and prints the
chip UID for the authorized workbench. It sends no server request and writes no
chip data. There is **no assumed factory/default key**, including all-zero keys.
An operator must obtain the actual bootstrap keys through their approved supply
process. Supply `--reader 'exact installed reader name'` if multiple readers are
connected; selection never silently picks one of several readers.

In the workbench, prepare an encoding plan for that UID, encoder and active card.
Use its plan UUID in the following command. The UUID and server origin are not
credentials.

## Personalize or recover

```sh
python3 -m nexencoder \
  --server https://school.example \
  --plan 33477421-580b-4585-9e2d-bdf9acec109f \
  --key-file ./encoder-private.pem \
  --outbox ./private-receipts \
  --execute-on-reader
```

The operator enters the key-file passphrase, `NED1` access code, and (for an
unconfirmed plan) all five existing bootstrap keys through hidden prompts.
The tool obtains the encrypted plan with an explicit bearer header and validates
its school/device/card/revision, UID, ten-minute lease, five nonzero issued keys,
NDEF template and SDM offsets. A different physical UID is refused before any
write. It checks the lease before each EV2 command, with a monotonic deadline
that cannot be extended by a backwards workstation clock correction.

Personalization uses these rules:

The local adapter first selects the NFC application and checks exactly three
GetVersion frames before authentication. The supported ordinary NTAG424 DNA
family has NXP vendor/type `04/04`, hardware major `30`, storage `11` and protocol
`05`; software type/subtype/major is `04/02/01` (minor0 or1). Other families and
TagTamper software variants are refused. The unauthenticated version descriptor
is a compatibility guard, not a claim of chip-manufacturer attestation; the
authenticated UID and keys must still match the plan.

1. Probe the issued master key0 first. If it already authenticates, **verify
   only**; do not rewrite the file or change keys. A server-confirmed plan also
   always requires this verify-only path.
2. Otherwise prove the existing master and resolve each other key as issued or
   operator-supplied old key. An unknown key stops the operation before writing.
3. Explicitly change file2 to FULL mode, write the complete 256-byte NDEF file
   in chunks of at most 192 bytes, and set the exact authorized SDM profile.
4. Change remaining old keys1–4, then key0 **last**. The master-key change's
   bare `9100` response retires the session; reauthenticate with the issued key.
5. Prove all five issued keys, UID and exact settings, then read the entire NDEF
   file in **one** ISO ReadBinary command. Check its immutable bytes and verify
   the encrypted PICC UID/counter plus empty-input SUN MAC locally.
6. Before POST, durably store an encrypted, source-bound receipt containing the
   exact operation UUID/PICC/MAC. This contains no chip keys. Only a bound valid
   success response removes the receipt.

Every failed or interrupted authenticated APDU retires its EV2 session; the tool
never guesses the counter or automatically retries a write. If power fails
while keys1–4 change, rerun the **same plan**: it probes the current key state
before computing any old/new XOR. If key0 changed but its reply was lost, the
issued master succeeds and the next run verifies without rewriting. Corrupt or
inconsistent issued-key/settings state stops for supervised recovery instead
of blind repair. A fresh GET renews the transport lease for the same authorized
source; revoked/deleted/changed sources remain refused by the server.

If confirmation times out, returns malformed/unbound success, or reports
408/425/429/5xx, rerun the same command and choose to retry the existing encrypted
receipt. It reauthorizes the current source and resends **the same operation
UUID/PICC/MAC without opening the reader**. Do not delete/replace an unresolved
receipt or manufacture a new operation to bypass a refusal. Definite refusal
also retains the receipt for investigation. Keep the encrypted local private
key available: another key cannot decrypt these receipts. Losing the key needs
an authorized server recovery process, not a plaintext export.

HTTP is refused except for an explicit `--allow-local-http-fixture` with a
literal loopback/localhost origin. TLS certificate verification stays enabled;
redirects, proxies and cookies are not used. Responses and files are bounded.
The CLI never prints APDUs, bootstrap/issued keys, access codes, private keys,
SUN proof bodies or raw exception diagnostics.

## Run software proofs without hardware

```sh
python3 -m unittest discover -s tests -t . -v
```

To also exercise the actual PHP Sodium/HKDF/OpenSSL envelope/profile and final
SUN-verification bridges,
set `CARD_INTEROP_PHP` to a PHP8.3 executable/wrapper with this checkout's
Composer autoload available. The bridge is database-free and uses random
synthetic chip keys:

```sh
CARD_INTEROP_PHP=php python3 -m unittest discover -s tests -t . -v
```

Without that variable the two PHP interoperability cases are explicitly skipped.
The remaining tests use no reader, driver, real card, real credential, database
or external network. One HTTP test binds an isolated loopback server. Published
NXP vectors anchor auth/session derivation, MAC and FULL commands/responses,
other-key CRC32, master-key bare success and SUN verification. The independent
emulator decrypts/verifies APDUs and mutates durable chip state; it does not echo
the runtime's commands. Faults cover each partial key change, lost master reply,
wrong UID/key, tampered response/file, lease expiry mid-write, and restart after
an unknown server response with exactly one physical personalization.
