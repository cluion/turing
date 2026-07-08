import { describe, expect, it } from 'vitest';
import { base64UrlEncode } from '../src/encoding';
import { solvePbkdf2 } from '../src/pow';

describe('solvePbkdf2', () => {
  it('finds a counter the caller planted', async () => {
    const salt = 's';
    const nonce = 'n';
    const cost = 30;
    const target = 5;
    const enc = new TextEncoder();
    const key = await crypto.subtle.importKey('raw', enc.encode(nonce + target), 'PBKDF2', false, ['deriveBits']);
    const bits = await crypto.subtle.deriveBits(
      { name: 'PBKDF2', salt: enc.encode(salt), iterations: cost, hash: 'SHA-256' },
      key,
      256,
    );
    const keySignature = base64UrlEncode(new Uint8Array(bits));

    const counter = await solvePbkdf2({ algorithm: 'PBKDF2-SHA256', salt, nonce, cost, keySignature }, 50);
    expect(counter).toBe(target);
  });
});
