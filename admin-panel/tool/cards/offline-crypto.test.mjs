import assert from 'node:assert/strict';
import {spawnSync} from 'node:child_process';
import test from 'node:test';
import {webcrypto} from 'node:crypto';
import {fileURLToPath} from 'node:url';
import {createOfflineReaderKey, OfflineCardSnapshot} from '../../public/assets/js/card-offline-crypto.mjs';

if (!globalThis.crypto) globalThis.crypto = webcrypto;
const deviceId = 'ce351ba8-ef98-445d-9712-19671e289f16';
async function fixture(empty = false) {
  const key = await createOfflineReaderKey();
  const process = spawnSync(globalThis.process.env.CARD_INTEROP_PHP ?? 'php', [fileURLToPath(new URL('../../tests/Support/card-offline-interop.php', import.meta.url))], {encoding: 'utf8', input: JSON.stringify({public_key: key.publicKey, empty}), maxBuffer: 1024 * 1024});
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
