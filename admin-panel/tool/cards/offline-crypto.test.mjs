import assert from 'node:assert/strict';
import {spawnSync} from 'node:child_process';
import test from 'node:test';
import {webcrypto} from 'node:crypto';
import {fileURLToPath} from 'node:url';
import {encryptPendingRecord, decryptPendingRecord} from '../../public/assets/js/card-offline-store.mjs';
import {createOfflineReaderKey, OfflineCardSnapshot} from '../../public/assets/js/card-offline-crypto.mjs';

if (!globalThis.crypto) globalThis.crypto = webcrypto;
const deviceId = 'ce351ba8-ef98-445d-9712-19671e289f16';
async function fixture(empty = false, requestId = null, proofOffset = 0) {
  const key = await createOfflineReaderKey();
  const process = spawnSync(globalThis.process.env.CARD_INTEROP_PHP ?? 'php', [fileURLToPath(new URL('../../tests/Support/card-offline-interop.php', import.meta.url))], {encoding: 'utf8', input: JSON.stringify({public_key: key.publicKey, empty, proof_offset: proofOffset, ...(requestId ? {request_id: requestId} : {})}), maxBuffer: 1024 * 1024});
  assert.equal(process.status, 0, process.stderr);
  return {key, ...JSON.parse(process.stdout)};
}
const open = (packet, key, options = {}) => OfflineCardSnapshot.open(packet, key.privateKey, {schoolId: 1, deviceId, trustedSigningKeys: new Map([[packet.signing_key_id, packet.signing_public_key]]), fetchedAt: 0, clock: () => 0, ...options});

test('actual PHP/Sodium packet opens with nonextractable WebCrypto key; QR consumes once', async () => {
  const {key, packet, proof} = await fixture();
  assert.equal(key.privateKey.extractable, false);
  await assert.rejects(crypto.subtle.exportKey('pkcs8', key.privateKey));
  const snapshot = await open(packet, key);
  let retired = 0;
  snapshot.addEventListener('retired', () => { retired++; });
  const result = await snapshot.scan(proof);
  assert.equal(result.status, 'active');
  assert.equal(result.revision, 3);
  assert.equal(result.personId, 12);
  assert.equal(new TextDecoder().decode(result.portrait), 'Synthetic protocol portrait bytes; not a decoded image.');
  assert.equal(result.collectionAuthorized, false);
  assert.equal(result.signInAuthorized, false);
  await assert.rejects(snapshot.scan(proof));
  snapshot.dispose();
  snapshot.dispose();
  assert.equal(retired, 1);
  assert.equal(result.portrait.every(byte => byte === 0), true);
  await assert.rejects(snapshot.scan(proof));
});

test('device/school/AAD/cipher/tag/key/manifest corruption cannot display a card', async () => {
  const {key, packet, proof} = await fixture();
  await assert.rejects(open(packet, await createOfflineReaderKey()));
  await assert.rejects(open(packet, key, {schoolId: 2}));
  await assert.rejects(open(packet, key, {deviceId: '77777777-7777-4777-8777-777777777777'}));
  await assert.rejects(open(packet, key, {trustedSigningKeys: new Map()}));
  for (const change of [p => {p.envelope.context.expires_at++;}, p => {p.envelope.context.snapshot_id = '77777777-7777-4777-8777-777777777777';},
    p => {p.envelope.ciphertext = 'AA==';}, p => {p.envelope.tag = 'AAAAAAAAAAAAAAAAAAAAAA==';},
    p => {p.signing_public_key = btoa('x'.repeat(32));}, p => {p.signing_key_id = '77777777-7777-4777-8777-777777777777';}]) {
    const altered = structuredClone(packet); change(altered);
    await assert.rejects(open(altered, key));
  }
  const snapshot = await open(packet, key);
  await assert.rejects(snapshot.scan(proof.replace('NEQ1.', 'NELOC1.')));
  await assert.rejects(snapshot.scan(proof.slice(0, -3) + 'AAA'));
  assert.throws(() => new OfflineCardSnapshot({}, {}, 0, () => 0));
  snapshot.dispose();
});

test('download, foreground, credential and rollback clocks never revive an expired snapshot', async () => {
  const {key, packet, proof} = await fixture();
  await assert.rejects(open(packet, key, {clock: () => 60000}));
  await assert.rejects(open(packet, key, {fetchedAt: undefined}));
  let time = 0;
  const snapshot = await open(packet, key, {clock: () => time});
  time = 25000;
  await assert.rejects(snapshot.scan(proof));
  time = 60000;
  await assert.rejects(snapshot.scan(proof));
  time = 0;
  await assert.rejects(snapshot.scan(proof));
  const second = await open(packet, key, {clock: () => time});
  time = -1;
  await assert.rejects(second.scan(proof));
  time = 0;
  await assert.rejects(second.scan(proof));
});


test('an empty authorized roster remains a valid encrypted response and grants no identity', async () => {
  const {key, packet, proof} = await fixture(true);
  const snapshot = await open(packet, key);
  await assert.rejects(snapshot.scan(proof));
  snapshot.dispose();
});


test('signed fresh challenge rejects an old packet and download time never becomes future scan time', async () => {
  const requestId = 'e92cbe2c-d93b-4c7f-bfde-d241ee88602e';
  const {key, packet, proof} = await fixture(false, requestId);
  await assert.rejects(open(packet, key));
  await assert.rejects(open(packet, key, {expectedRequestId: deviceId}));
  const missing = structuredClone(packet); delete missing.request_id;
  await assert.rejects(open(missing, key, {expectedRequestId: requestId}));
  const substituted = structuredClone(packet); substituted.request_id = deviceId;
  await assert.rejects(open(substituted, key, {expectedRequestId: deviceId}));
  let time = 5000;
  const snapshot = await open(packet, key, {expectedRequestId: requestId, receivedAt: 5000, clock: () => time});
  assert.equal(snapshot.count, 1);
  time = 6000;
  const result = await snapshot.scan(proof);
  assert.equal(result.scannedAt, 1001); // receipt anchor: not pre-download 1006
  assert.match(result.nonceHash, /^[a-f0-9]{64}$/);
  assert.equal(result.proofExpiresAt, 1025);
  time = 60000;
  assert.throws(() => snapshot.count);
  assert.equal(result.portrait.every(byte => byte === 0), true);
});


test('pending retry encrypts exact original operation and rejects cross-device or tampered packets', async () => {
  const key = await createOfflineReaderKey(), other = await createOfflineReaderKey();
  const operation = crypto.randomUUID();
  const scope = {schoolId: 1, deviceId, keyId: crypto.randomUUID()};
  const input = {operation_id: operation, manifest: 'NEOS1.synthetic-immutable-manifest', credential: 'NEQ1.synthetic-immutable-proof', scanned_at: 1001};
  const packet = await encryptPendingRecord(key.publicKey, scope, input);
  assert.equal(JSON.stringify(packet).includes(input.credential), false);
  assert.equal(JSON.stringify(packet).includes(input.manifest), false);
  assert.deepEqual(await decryptPendingRecord(key.privateKey, scope, operation, packet), input);
  await assert.rejects(decryptPendingRecord(other.privateKey, scope, operation, packet));
  await assert.rejects(decryptPendingRecord(key.privateKey, {...scope, schoolId: 2}, operation, packet));
  await assert.rejects(decryptPendingRecord(key.privateKey, {...scope, deviceId: scope.keyId}, operation, packet));
  await assert.rejects(decryptPendingRecord(key.privateKey, scope, crypto.randomUUID(), packet));
  const altered = structuredClone(packet); altered.ciphertext = btoa('tampered');
  await assert.rejects(decryptPendingRecord(key.privateKey, scope, operation, altered));
  await assert.rejects(encryptPendingRecord(key.publicKey, scope, {...input, another: true}));
});


test('whole-second server timestamp uncertainty permits a fresh next-second proof and retires conservatively', async () => {
  const {key, packet, proof} = await fixture(false, null, 1);
  let time = 1;
  const snapshot = await open(packet, key, {receivedAt: 1, clock: () => time});
  const result = await snapshot.scan(proof);
  assert.equal(result.scannedAt, 1001);
  time = 59000;
  assert.throws(() => snapshot.count);
  assert.equal(result.portrait.every(byte => byte === 0), true);
});
