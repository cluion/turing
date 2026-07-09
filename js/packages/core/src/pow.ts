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

// Upper bound on the base64url-decoded keySignature. The server derives a
// 32-byte SHA-256 key; anything larger is a malformed/hostile challenge and is
// rejected before it becomes a huge, slow deriveBits length.
const MAX_KEY_BYTES = 64;

// Ceiling on PBKDF2 iterations per derivation so a hostile `cost` cannot make a
// single deriveBits call arbitrarily expensive (server default is 5000).
const MAX_PBKDF2_COST = 1_000_000;

// Ceiling on SHA-bit difficulty so `2 ** bits` stays finite. The wall-clock
// deadline below is what actually bounds the work; this only rejects nonsense.
const MAX_SHABIT_BITS = 32;

// Hard ceiling on the SHA-bit counter budget regardless of difficulty, so the
// loop always terminates in bounded time and fails fast to the error state
// instead of spinning indefinitely on a hostile difficulty.
const MAX_SHABIT_COUNTER = 5_000_000;

// Wall-clock budget for a whole solve. Whichever limit (counter or time) is hit
// first ends the loop; a hostile/misconfigured challenge fails fast rather than
// pinning the tab for hours. Callers can override per solve.
const DEFAULT_DEADLINE_MS = 30_000;

/**
 * Default counter budget for a SHA-bit challenge: 16x the expected 2^bits
 * trials so a legitimate client practically never exhausts the loop (spurious
 * failure ~ e^-16), clamped to a hard ceiling so a hostile difficulty stays
 * bounded rather than effectively infinite.
 */
export function shaBitBudget(difficultyBits: number): number {
  return Math.min(16 * 2 ** difficultyBits, MAX_SHABIT_COUNTER);
}

/**
 * Brute-force the counter whose PBKDF2 derived key equals keySignature.
 * Mirrors PHP hash_pbkdf2('sha256', nonce+counter, salt, cost). Returns the
 * counter, or throws if the params are malformed or none is found within
 * maxCounter.
 */
export async function solvePbkdf2(
  params: Pbkdf2Params,
  maxCounter = 100000,
  deadlineMs = DEFAULT_DEADLINE_MS,
  signal?: AbortSignal,
): Promise<number> {
  if (
    typeof params.salt !== 'string' ||
    typeof params.nonce !== 'string' ||
    typeof params.keySignature !== 'string' ||
    !Number.isInteger(params.cost) ||
    params.cost < 1 ||
    params.cost > MAX_PBKDF2_COST
  ) {
    throw new Error('invalid PBKDF2 challenge params');
  }
  const dkLen = base64UrlDecode(params.keySignature).length;
  if (dkLen < 1 || dkLen > MAX_KEY_BYTES) {
    throw new Error('invalid keySignature length');
  }
  const saltBytes = encoder.encode(params.salt);
  const dkLenBits = dkLen * 8;
  const deadline = performance.now() + deadlineMs;

  for (let counter = 1; counter <= maxCounter; counter++) {
    if (signal?.aborted) {
      throw new Error('aborted');
    }
    if (performance.now() > deadline) {
      throw new Error('PoW time budget exceeded');
    }
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
 * difficulty_bits leading zero bits. Mirrors PHP ShaBitSolver. The default
 * budget scales with difficulty so it stays matched to the server config
 * instead of a fixed ceiling that a difficulty of 20 would routinely exceed.
 */
export async function solveShaBit(
  params: ShaBitParams,
  maxCounter = shaBitBudget(params.difficulty_bits),
  deadlineMs = DEFAULT_DEADLINE_MS,
  signal?: AbortSignal,
): Promise<number> {
  if (
    typeof params.salt !== 'string' ||
    !Number.isInteger(params.difficulty_bits) ||
    params.difficulty_bits < 0 ||
    params.difficulty_bits > MAX_SHABIT_BITS
  ) {
    throw new Error('invalid SHA-bit challenge params');
  }
  const deadline = performance.now() + deadlineMs;
  for (let counter = 0; counter <= maxCounter; counter++) {
    if (signal?.aborted) {
      throw new Error('aborted');
    }
    if (performance.now() > deadline) {
      throw new Error('PoW time budget exceeded');
    }
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
