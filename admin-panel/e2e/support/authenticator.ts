import { createHmac } from 'node:crypto';

// Test-side authenticator for the setup key shown to this synthetic operator.
export function authenticatorCode(setupKey: string): string {
  const alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
  const bits = setupKey.replace(/\s/g, '').split('').map(char => {
    const value = alphabet.indexOf(char);
    if (value < 0) throw new Error('Invalid authenticator setup key');
    return value.toString(2).padStart(5, '0');
  }).join('');
  const key = Buffer.from(bits.match(/.{8}/g)!.map(byte => parseInt(byte, 2)));
  const counter = Buffer.alloc(8);
  counter.writeBigUInt64BE(BigInt(Math.floor(Date.now() / 30000)));
  const digest = createHmac('sha1', key).update(counter).digest();
  const offset = digest[digest.length - 1] & 15;
  return String((digest.readUInt32BE(offset) & 0x7fffffff) % 1000000).padStart(6, '0');
}

