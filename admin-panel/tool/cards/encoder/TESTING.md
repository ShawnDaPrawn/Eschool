# Encoder software evidence — 26 October2026

Command run from this directory:

```sh
CARD_INTEROP_PHP=/workspace/scratch/test-php \
  python3 -m unittest discover -s tests -t . -v
python3 -m compileall -q nexencoder tests
python3 -m nexencoder --help
```

Result: **46 tests passed, zero failures or skips**. The PHP8.3.36
wrapper executes the actual `CardEncodingCipher` and `NtagEncodingProfile`
  through the database-free `tests/Support/card-encoding-interop.php` bridge.
Python uses the pinned installed `cryptography==50.0.0`.

Evidence groups:

- Table12: exact bounded three-frame GetVersion command/responses. Independent
  emulator drives the real ISO/native adapter methods end to end; unsupported
  chip family/TagTamper software variants, bad lengths and excess chaining stop
  before authentication or mutation. This does not call the PC/SC shared library.
- NXP AN12196 Rev.2.0, 4 March2025, table14: exact two authentication APDUs,
  zero IV per auth message, nonce proof and both published session keys.
- Table7: exact MAC GetFileSettings command and authenticated plain response.
- Table18: exact FULL ChangeFileSettings ciphertext, truncated command CMAC,
  incremented response counter/CMAC.
- Tables25/26: published other-key complemented CRC32 `789DFADC`, complete
  encrypted ChangeKey APDUs, and key0 bare `9100`/session retirement.
- Table4: coherent encrypted PICC metadata, UID, read counter and SUN MAC.
- Independent emulator: actual command decrypt/MAC checks and persistent key/
  file mutations. Proves explicit FULL mode, complete 256-byte two-chunk write,
  keys1–4 before master0, each partial-key power cut, lost master acknowledgement
  recovery, confirmed verify-only, wrong UID/key refusal before writes, corrupt
  file/settings/response refusal and lease expiry between write chunks.
- Actual PHP-to-Python random X25519/Sodium→HKDF→OpenSSL/GCM envelope/profile
  interop. Wrong key/purpose/context order/source/version/schema, ciphertext/AAD
  tampering, malformed/noncanonical base64/JSON, unsafe key material, expiry and
  backwards local-clock extension are refused.
- Actual Python personalization/independent chip emulator final coherent SUN
  is passed to the server's real `SunVerifier`/`AesCmac` through the database-free
  `tests/Support/card-encoding-sun-verify.php` bridge. PHP accepts it and returns
  the exact locally verified counter after the two writes/all five key changes.
- A real failing receipt-boundary regression first proved that valid counter0
  was being rejected despite the server's24-bit contract. The corrected API
  decoder accepts0 through `0xFFFFFF`, while refusing booleans, negatives,
  strings and overflow. An already committed first counter0 can retire its
  encrypted outbox receipt instead of remaining in permanent unknown state.
- Real loopback HTTP redirect refusal with explicit bearer, no cookies or proxy;
  bounded replies and unbound/malformed success handling. All uncertain
  408/425/429/5xx/network outcomes preserve exact confirmation semantics.
- Actual encrypted PKCS8, owner-only regular file/directory rules, symlink/
  hardlink refusal, encrypted durable outbox, no overwrite of unresolved intent,
  wrong key/origin/integrity refusal. Complete CLI/emulator journey commits chip
  state, loses the server reply, restarts and replays the same operation/PICC/MAC
  without opening or rewriting the reader. Captured output contains no secret.

No real credentials, chip keys or production records are used in tests. Named
vector constants are published synthetic examples, not provisioning defaults.
No physical PC/SC access, card write, reader/driver install, factory process,
hardware provisioning, paid service or production activation was performed.
The Windows adapter/ACL path and physical reader/card combinations still require
their own deployment qualification. macOS is explicitly refused pending ABI
qualification. Those remaining checks are not represented as passing here.
