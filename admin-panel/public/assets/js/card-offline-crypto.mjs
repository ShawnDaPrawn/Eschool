/* Public-key-only offline identity verification. No collection or sign-in grant. */
const suite = 'X25519-HKDF-SHA256-A256GCM';
const uuid = /^[a-f0-9]{8}-[a-f0-9]{4}-[1-8][a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/;
const encoder = new TextEncoder();
const decoder = new TextDecoder('utf-8', {fatal: true});
const initialized = Symbol('verified-offline-snapshot');
const integer = value => Number.isSafeInteger(value) && value > 0;
const fail = () => { throw new Error('Reconnect to verify this card and refresh the offline reader.'); };
const base64 = bytes => btoa(String.fromCharCode(...bytes));
function bytes(text, limit, url = false) {
  if (typeof text !== 'string' || !text || text.length > Math.ceil(limit * 4 / 3) + 4) fail();
  let source = text;
  if (url) {
    if (!/^[A-Za-z0-9_-]+$/.test(text) || text.length % 4 === 1) fail();
    source = text.replaceAll('-', '+').replaceAll('_', '/') + '='.repeat((4 - text.length % 4) % 4);
  } else if (!/^(?:[A-Za-z0-9+/]{4})*(?:[A-Za-z0-9+/]{2}==|[A-Za-z0-9+/]{3}=)?$/.test(text)) fail();
  const result = Uint8Array.from(atob(source), char => char.charCodeAt(0));
  // Chunk conversion avoids a call-stack overflow for encrypted roster pages.
  let encoded = '';
  for (let start = 0; start < result.length; start += 8192) encoded += String.fromCharCode(...result.subarray(start, start + 8192));
  const canonical = url ? btoa(encoded).replaceAll('+', '-').replaceAll('/', '_').replace(/=+$/, '') : btoa(encoded);
  if (result.length > limit || canonical !== text) fail();
  return result;
}
const object = value => value !== null && typeof value === 'object' && !Array.isArray(value);
const sha256 = async value => Array.from(new Uint8Array(await crypto.subtle.digest('SHA-256', value)), byte => byte.toString(16).padStart(2, '0')).join('');

export async function createOfflineReaderKey() {
  const pair = await crypto.subtle.generateKey({name: 'X25519'}, false, ['deriveBits']);
  return {privateKey: pair.privateKey, publicKey: base64(new Uint8Array(await crypto.subtle.exportKey('raw', pair.publicKey)))};
}

export class OfflineCardSnapshot extends EventTarget {
  #metadata;
  #manifest;
  #portraits;
  #started;
  #clock;
  #seen = new Set();
  #leases = new Set();
  #elapsed = 0;
  #timer;
  #retired = false;

  constructor(token, metadata, manifest, portraits, started, clock) {
    super();
    if (token !== initialized) fail();
    this.#metadata = metadata;
    this.#manifest = manifest;
    this.#portraits = portraits;
    this.#started = started;
    this.#clock = clock;
    this.#timer = setTimeout(() => this.dispose(), Math.max(0, 60000 - (clock() - started)));
    this.#timer?.unref?.();
  }

  #now() {
    const elapsed = this.#clock() - this.#started;
    if (this.#retired || !Number.isFinite(elapsed) || elapsed < this.#elapsed || elapsed >= 60000) {
      this.dispose();
      fail();
    }
    this.#elapsed = elapsed;
    return this.#metadata.i + Math.floor(elapsed / 1000);
  }

  static async open(packet, privateKey, {schoolId, deviceId, trustedSigningKeys, fetchedAt, clock = () => performance.now()} = {}) {
    if (!object(packet) || !object(packet.envelope) || !integer(schoolId) || !uuid.test(deviceId ?? '')) fail();
    const envelope = packet.envelope, c = envelope.context;
    if (envelope.suite !== suite || !object(c) || c.school_id !== schoolId || c.device_id !== deviceId || !uuid.test(c.snapshot_id ?? '') ||
        !integer(c.issued_at) || !integer(c.expires_at) || c.expires_at - c.issued_at !== 60 || !uuid.test(packet.signing_key_id ?? '') ||
        privateKey?.type !== 'private' || privateKey.algorithm?.name !== 'X25519' || privateKey.extractable) fail();
    // A self-announced key in an encrypted cache is not a trust anchor. The
    // owner supplies a separate keyring obtained from authenticated HTTPS.
    if (!(trustedSigningKeys instanceof Map) || trustedSigningKeys.get(packet.signing_key_id) !== packet.signing_public_key) fail();
    if (!Number.isFinite(fetchedAt)) fail();
    const elapsed = clock() - fetchedAt;
    if (!Number.isFinite(elapsed) || elapsed < 0 || elapsed >= 60000) fail();
    const context = {school_id: c.school_id, device_id: c.device_id, snapshot_id: c.snapshot_id, issued_at: c.issued_at, expires_at: c.expires_at};
    const aad = encoder.encode(JSON.stringify({suite, ...context}));
    const peer = await crypto.subtle.importKey('raw', bytes(envelope.ephemeral_public_key, 32), {name: 'X25519'}, false, []);
    const shared = new Uint8Array(await crypto.subtle.deriveBits({name: 'X25519', public: peer}, privateKey, 256));
    let plaintext;
    try {
      const hkdf = await crypto.subtle.importKey('raw', shared, 'HKDF', false, ['deriveKey']);
      const key = await crypto.subtle.deriveKey({name: 'HKDF', hash: 'SHA-256', salt: bytes(envelope.salt, 32), info: aad}, hkdf, {name: 'AES-GCM', length: 256}, false, ['decrypt']);
      const encrypted = bytes(envelope.ciphertext, 3 * 1024 * 1024), tag = bytes(envelope.tag, 16);
      if (tag.length !== 16) fail();
      const complete = new Uint8Array(encrypted.length + tag.length);
      complete.set(encrypted); complete.set(tag, encrypted.length);
      plaintext = new Uint8Array(await crypto.subtle.decrypt({name: 'AES-GCM', iv: bytes(envelope.nonce, 12), additionalData: aad, tagLength: 128}, key, complete));
      const value = JSON.parse(decoder.decode(plaintext));
      if (!object(value) || typeof value.manifest !== 'string' || value.manifest.length > 131072 || !object(value.portraits)) fail();
      const parts = value.manifest.split('.');
      if (parts.length !== 4 || parts[0] !== 'NEOS1' || parts[1] !== packet.signing_key_id) fail();
      const publicKey = await crypto.subtle.importKey('raw', bytes(packet.signing_public_key, 32), 'Ed25519', false, ['verify']);
      if (!await crypto.subtle.verify('Ed25519', publicKey, bytes(parts[3], 64, true), encoder.encode(parts.slice(0, 3).join('.')))) fail();
      const metadata = JSON.parse(decoder.decode(bytes(parts[2], 98304, true)));
      if (!object(metadata) || metadata.v !== 1 || metadata.s !== schoolId || metadata.d !== deviceId || metadata.id !== c.snapshot_id ||
          metadata.i !== c.issued_at || metadata.e !== c.expires_at || !Array.isArray(metadata.cards) || metadata.cards.length > 100 || !Array.isArray(metadata.keys) || metadata.keys.length > 16) fail();
      const ids = new Set();
      for (const card of metadata.cards) {
        if (!object(card) || !uuid.test(card.id ?? '') || ids.has(card.id) || !integer(card.r) || !integer(card.u) || !['learner', 'guardian', 'staff'].includes(card.t) || !/^[a-f0-9]{64}$/.test(card.p ?? '')) fail();
        ids.add(card.id);
        const image = bytes(value.portraits[card.id], 65536);
        try { if (await sha256(image) !== card.p) fail(); } finally { image.fill(0); }
      }
      if (Object.keys(value.portraits).length !== ids.size) fail();
      const keyIds = new Set();
      for (const key of metadata.keys) {
        if (!object(key) || !uuid.test(key.id ?? '') || keyIds.has(key.id) || !integer(key.starts_at) || !integer(key.sign_until) || !integer(key.verify_until) || key.starts_at >= key.sign_until || key.sign_until > key.verify_until || bytes(key.public_key, 32).length !== 32) fail();
        keyIds.add(key.id);
      }
      const snapshot = new OfflineCardSnapshot(initialized, metadata, value.manifest, value.portraits, fetchedAt, clock);
      snapshot.#now();
      return snapshot;
    } finally {
      shared.fill(0);
      plaintext?.fill(0);
    }
  }

  async scan(credential) {
    const now = this.#now();
    if (typeof credential !== 'string' || credential.length > 1024 || this.#seen.size >= 1000) fail();
    const parts = credential.split('.');
    if (parts.length !== 3 || parts[0] !== 'NEQ1') fail();
    const claims = JSON.parse(decoder.decode(bytes(parts[1], 768, true)));
    if (!object(claims) || Object.keys(claims).join(',') !== 'v,s,c,r,k,i,e,n' || claims.v !== 1 || claims.s !== this.#metadata.s || !uuid.test(claims.c ?? '') || !uuid.test(claims.k ?? '') || !integer(claims.r) ||
        !integer(claims.i) || !integer(claims.e) || claims.e - claims.i < 10 || claims.e - claims.i > 30 || claims.i > now || claims.e <= now || !/^[a-f0-9]{32}$/.test(claims.n ?? '') || this.#seen.has(claims.n)) fail();
    const card = this.#metadata.cards.find(item => item.id === claims.c && item.r === claims.r);
    const key = this.#metadata.keys.find(item => item.id === claims.k);
    if (!card || !key || claims.i < key.starts_at || claims.i >= key.sign_until || now >= key.verify_until) fail();
    const publicKey = await crypto.subtle.importKey('raw', bytes(key.public_key, 32), 'Ed25519', false, ['verify']);
    if (!await crypto.subtle.verify('Ed25519', publicKey, bytes(parts[2], 64, true), encoder.encode(parts.slice(0, 2).join('.')))) fail();
    const checkedAt = this.#now();
    if (claims.e <= checkedAt || this.#seen.has(claims.n)) fail();
    this.#seen.add(claims.n);
    const portrait = bytes(this.#portraits[card.id], 65536);
    this.#leases.add(portrait);
    return {cardId: card.id, personId: card.u, type: card.t, revision: card.r, status: 'active', portrait,
      scannedAt: checkedAt, manifest: this.#manifest, collectionAuthorized: false, signInAuthorized: false};
  }

  dispose() {
    if (this.#retired) return;
    this.#retired = true;
    clearTimeout(this.#timer);
    for (const lease of this.#leases) lease.fill(0);
    this.#leases.clear();
    if (this.#portraits) for (const id of Object.keys(this.#portraits)) this.#portraits[id] = '';
    this.#portraits = null;
    this.#metadata = null;
    this.#manifest = null;
    this.#seen.clear();
    this.dispatchEvent(new Event('retired'));
  }
}
