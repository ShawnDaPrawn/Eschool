/* Persist only nonextractable device keys, opaque nonce claims and encrypted logs. */
const encode = new TextEncoder(), decode = new TextDecoder('utf-8', {fatal: true});
const uuid = /^[a-f0-9]{8}-[a-f0-9]{4}-[1-8][a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/;
const suite = 'NED-PENDING-X25519-HKDF-SHA256-A256GCM-1';
const fail = () => { throw new Error('Protected reader storage is unavailable. Reconnect or use the online reader.'); };
function b64(bytes) {
  let text = '';
  for (let i = 0; i < bytes.length; i += 8192) text += String.fromCharCode(...bytes.subarray(i, i + 8192));
  return btoa(text);
}
function raw(value, maximum) {
  if (typeof value !== 'string' || value.length > Math.ceil(maximum * 4 / 3) + 4 || !/^(?:[A-Za-z0-9+/]{4})*(?:[A-Za-z0-9+/]{2}==|[A-Za-z0-9+/]{3}=)?$/.test(value)) fail();
  const result = Uint8Array.from(atob(value), char => char.charCodeAt(0));
  if (result.length > maximum || b64(result) !== value) fail();
  return result;
}
function context(scope, operation) {
  if (!Number.isSafeInteger(scope.schoolId) || scope.schoolId < 1 || !uuid.test(scope.deviceId ?? '') || !uuid.test(scope.keyId ?? '') || !uuid.test(operation ?? '')) fail();
  return {suite, school_id: scope.schoolId, device_id: scope.deviceId, key_id: scope.keyId, operation_id: operation};
}
async function aes(shared, salt, aad, usages) {
  const seed = await crypto.subtle.importKey('raw', shared, 'HKDF', false, ['deriveKey']);
  return crypto.subtle.deriveKey({name: 'HKDF', hash: 'SHA-256', salt, info: aad}, seed, {name: 'AES-GCM', length: 256}, false, usages);
}
export async function encryptPendingRecord(publicKey, scope, input) {
  if (Object.keys(input).sort().join(',') !== 'credential,manifest,operation_id,scanned_at' || typeof input.manifest !== 'string' || input.manifest.length > 131072 ||
      !input.manifest.startsWith('NEOS1.') || typeof input.credential !== 'string' || input.credential.length > 1024 || !input.credential.startsWith('NEQ1.') || !Number.isSafeInteger(input.scanned_at) || input.scanned_at < 1) fail();
  const binding = context(scope, input.operation_id), aad = encode.encode(JSON.stringify(binding));
  const pair = await crypto.subtle.generateKey({name: 'X25519'}, false, ['deriveBits']);
  const recipient = await crypto.subtle.importKey('raw', raw(publicKey, 32), 'X25519', false, []);
  const shared = new Uint8Array(await crypto.subtle.deriveBits({name: 'X25519', public: recipient}, pair.privateKey, 256));
  const salt = crypto.getRandomValues(new Uint8Array(32)), nonce = crypto.getRandomValues(new Uint8Array(12)), plain = encode.encode(JSON.stringify(input));
  try {
    const key = await aes(shared, salt, aad, ['encrypt']);
    const encrypted = await crypto.subtle.encrypt({name: 'AES-GCM', iv: nonce, additionalData: aad}, key, plain);
    return {context: binding, ephemeral_public_key: b64(new Uint8Array(await crypto.subtle.exportKey('raw', pair.publicKey))), salt: b64(salt), nonce: b64(nonce), ciphertext: b64(new Uint8Array(encrypted))};
  } finally { shared.fill(0); plain.fill(0); }
}
export async function decryptPendingRecord(privateKey, scope, operation, packet) {
  const binding = context(scope, operation);
  if (!packet || JSON.stringify(packet.context) !== JSON.stringify(binding) || privateKey?.type !== 'private' || privateKey.extractable || privateKey.algorithm?.name !== 'X25519') fail();
  const aad = encode.encode(JSON.stringify(binding));
  const peer = await crypto.subtle.importKey('raw', raw(packet.ephemeral_public_key, 32), 'X25519', false, []);
  const shared = new Uint8Array(await crypto.subtle.deriveBits({name: 'X25519', public: peer}, privateKey, 256));
  let plain;
  try {
    const key = await aes(shared, raw(packet.salt, 32), aad, ['decrypt']);
    plain = new Uint8Array(await crypto.subtle.decrypt({name: 'AES-GCM', iv: raw(packet.nonce, 12), additionalData: aad}, key, raw(packet.ciphertext, 200000)));
    const value = JSON.parse(decode.decode(plain));
    if (value?.operation_id !== operation || Object.keys(value).sort().join(',') !== 'credential,manifest,operation_id,scanned_at') fail();
    return value;
  } finally { shared.fill(0); plain?.fill(0); }
}

function transaction(db, names, mode, action) {
  return new Promise((resolve, reject) => {
    const tx = db.transaction(names, mode);
    let value;
    tx.oncomplete = () => resolve(value);
    tx.onabort = tx.onerror = () => reject(new Error('The protected record could not be saved or was already used.'));
    try { action(tx, result => { value = result; }); } catch (error) { tx.abort(); reject(error); }
  });
}
export class OfflineReaderStore {
  #db;
  constructor(db) { this.#db = db; }
  static async open(name = 'navigateed-card-reader-v1') {
    if (!globalThis.indexedDB) fail();
    const db = await new Promise((resolve, reject) => {
      const request = indexedDB.open(name, 1);
      request.onupgradeneeded = () => {
        request.result.createObjectStore('keys', {keyPath: 'id'});
        const pending = request.result.createObjectStore('pending', {keyPath: 'id'});
        pending.createIndex('key_id', 'key_id');
        const nonces = request.result.createObjectStore('nonces', {keyPath: 'id'});
        nonces.createIndex('key_id', 'key_id');
      };
      request.onsuccess = () => resolve(request.result);
      request.onerror = request.onblocked = () => reject(new Error('Protected reader storage cannot open.'));
    });
    return new OfflineReaderStore(db);
  }
  close() { this.#db.close(); }
  async createKey(key) {
    if (key.privateKey?.type !== 'private' || key.privateKey?.extractable || key.privateKey?.algorithm?.name !== 'X25519' || raw(key.publicKey, 32).length !== 32) fail();
    const row = {id: crypto.randomUUID(), privateKey: key.privateKey, publicKey: key.publicKey, schoolId: null, deviceId: null};
    await transaction(this.#db, ['keys'], 'readwrite', (tx) => { tx.objectStore('keys').add(row); });
    return row;
  }
  async keys() {
    return transaction(this.#db, ['keys'], 'readonly', (tx, result) => {
      const request = tx.objectStore('keys').getAll();
      request.onsuccess = () => result(request.result.map(({id, publicKey, schoolId, deviceId}) => ({id, publicKey, schoolId, deviceId})));
    });
  }
  async key(id) {
    return transaction(this.#db, ['keys'], 'readonly', (tx, result) => {
      const request = tx.objectStore('keys').get(id); request.onsuccess = () => result(request.result);
    });
  }
  async bind(keyId, schoolId, deviceId) {
    context({keyId, schoolId, deviceId}, crypto.randomUUID());
    return transaction(this.#db, ['keys'], 'readwrite', (tx, result) => {
      const store = tx.objectStore('keys'), request = store.get(keyId);
      request.onsuccess = () => {
        const row = request.result;
        if (!row || (row.schoolId !== null && (row.schoolId !== schoolId || row.deviceId !== deviceId))) { tx.abort(); return; }
        row.schoolId = schoolId; row.deviceId = deviceId; store.put(row); result(row);
      };
    });
  }
  async claimAndQueue(key, scope, input, nonceHash, expiresAt) {
    if (key.id !== scope.keyId || key.schoolId !== scope.schoolId || key.deviceId !== scope.deviceId || !/^[a-f0-9]{64}$/.test(nonceHash) || !Number.isSafeInteger(expiresAt)) fail();
    const packet = await encryptPendingRecord(key.publicKey, scope, input);
    return transaction(this.#db, ['pending', 'nonces'], 'readwrite', (tx) => {
      // The nonce and exact original UUID enter storage atomically before any
      // offline photo/acceptance is displayed, including in a second tab.
      tx.objectStore('nonces').add({id: `${scope.schoolId}:${scope.deviceId}:${nonceHash}`, key_id: key.id, expires_at: expiresAt});
      const pending = tx.objectStore('pending'), count = pending.index('key_id').count(key.id);
      count.onsuccess = () => {
        if (count.result >= 100) { tx.abort(); return; }
        pending.add({id: input.operation_id, key_id: key.id, school_id: scope.schoolId, device_id: scope.deviceId, packet});
      };
    });
  }
  async pending(scope) {
    return transaction(this.#db, ['pending'], 'readonly', (tx, result) => {
      const request = tx.objectStore('pending').index('key_id').getAll(scope.keyId, 50);
      request.onsuccess = () => result(request.result.filter(row => row.school_id === scope.schoolId && row.device_id === scope.deviceId));
    });
  }
  async removePending(scope, id) {
    return transaction(this.#db, ['pending'], 'readwrite', tx => {
      const store = tx.objectStore('pending'), request = store.get(id);
      request.onsuccess = () => {
        const row = request.result;
        if (row && (row.key_id !== scope.keyId || row.school_id !== scope.schoolId || row.device_id !== scope.deviceId)) { tx.abort(); return; }
        store.delete(id);
      };
    });
  }
  async pruneNonces(scope, authenticatedServerTime) {
    if (!Number.isSafeInteger(authenticatedServerTime) || authenticatedServerTime < 1) fail();
    return transaction(this.#db, ['nonces'], 'readwrite', tx => {
      const request = tx.objectStore('nonces').index('key_id').openCursor(IDBKeyRange.only(scope.keyId));
      request.onsuccess = () => {
        const cursor = request.result;
        if (!cursor) return;
        // Only a fresh authenticated server epoch may age out local claims.
        // A page clock or an offline cache reload never resets replay history.
        if (cursor.value.expires_at < authenticatedServerTime - 60) cursor.delete();
        cursor.continue();
      };
    });
  }
}
