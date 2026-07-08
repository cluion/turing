import { base64UrlDecode, base64UrlEncode } from './encoding';

/** Deterministic PBKDF2 challenge params (from the server). */
export interface Pbkdf2Params {
  algorithm: string;
  salt: string;
  nonce: string;
  cost: number;
  keySignature: string;
}

/** Hashcash-style SHA-256 leading-zero-bit challenge params. */
export interface ShaBitParams {
  algorithm: string;
  salt: string;
  difficulty_bits: number;
}

const encoder = new TextEncoder();

/**
 * Brute-force the counter whose PBKDF2 derived key equals keySignature.
 * Mirrors PHP hash_pbkdf2('sha256', nonce+counter, salt, cost). Returns the
 * counter, or throws if none is found within maxCounter.
 */
export async function solvePbkdf2(params: Pbkdf2Params, maxCounter = 100000): Promise<number> {
  const saltBytes = encoder.encode(params.salt);
  const dkLenBits = base64UrlDecode(params.keySignature).length * 8;

  for (let counter = 1; counter <= maxCounter; counter++) {
    const key = await crypto.subtle.importKey(
      'raw',
      encoder.encode(params.nonce + counter),
      'PBKDF2',
      false,
      ['deriveBits'],
    );
    const bits = await crypto.subtle.deriveBits(
      { name: 'PBKDF2', salt: saltBytes, iterations: params.cost, hash: 'SHA-256' },
      key,
      dkLenBits,
    );
    if (base64UrlEncode(new Uint8Array(bits)) === params.keySignature) {
      return counter;
    }
  }
  throw new Error('PoW solution not found within maxCounter');
}

/**
 * Brute-force the counter whose SHA-256(salt+counter) has at least
 * difficulty_bits leading zero bits. Mirrors PHP ShaBitSolver.
 */
export async function solveShaBit(params: ShaBitParams, maxCounter = 1_000_000): Promise<number> {
  for (let counter = 0; counter <= maxCounter; counter++) {
    const digest = new Uint8Array(
      await crypto.subtle.digest('SHA-256', encoder.encode(params.salt + counter)),
    );
    if (leadingZeroBits(digest) >= params.difficulty_bits) {
      return counter;
    }
  }
  throw new Error('PoW solution not found within maxCounter');
}

/**
 * Count the leading zero bits of a byte array (mirrors PHP leadingZeroBits).
 */
function leadingZeroBits(bytes: Uint8Array): number {
  let bits = 0;
  for (const byte of bytes) {
    if (byte === 0) {
      bits += 8;
      continue;
    }
    for (let i = 7; i >= 0; i--) {
      if ((byte >> i) & 1) {
        return bits;
      }
      bits++;
    }
  }
  return bits;
}
