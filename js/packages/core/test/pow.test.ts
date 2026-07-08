import { describe, expect, it } from 'vitest';
import { base64UrlEncode } from '../src/encoding';
import { shaBitBudget, solvePbkdf2, solveShaBit } from '../src/pow';

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

  it('rejects an oversized keySignature instead of deriving a huge key', async () => {
    const huge = base64UrlEncode(new Uint8Array(128));
    await expect(
      solvePbkdf2({ algorithm: 'PBKDF2-SHA256', salt: 's', nonce: 'n', cost: 30, keySignature: huge }, 50),
    ).rejects.toThrow(/keySignature length/);
  });
});

describe('shaBitBudget', () => {
  it('covers the server default difficulty (20) well above the mean 2^20', () => {
    expect(shaBitBudget(20)).toBeGreaterThan(2 ** 20);
  });
});

describe('solveShaBit', () => {
  it('solves with the difficulty-scaled default budget (no explicit maxCounter)', async () => {
    const counter = await solveShaBit({ algorithm: 'SHA-256', salt: 'vsalt', difficulty_bits: 4 });
    expect(counter).toBe(0);
  });

  it('rejects an out-of-range difficulty', async () => {
    await expect(solveShaBit({ algorithm: 'SHA-256', salt: 's', difficulty_bits: 99 })).rejects.toThrow(
      /invalid SHA-bit/,
    );
  });
});
