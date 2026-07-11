import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { solveInWorker } from '../src/solve';

function loadVector(name: string): { challenge: unknown; correctCounter: number } {
  const url = new URL(`../../../../php/tests/vectors/${name}`, import.meta.url);
  return JSON.parse(readFileSync(fileURLToPath(url).replace('/@fs/', '/'), 'utf8'));
}

describe('solveInWorker (inline fallback)', () => {
  afterEach(() => {
    vi.unstubAllGlobals();
    vi.restoreAllMocks();
  });

  it('solves via inline path when Worker is unavailable', async () => {
    expect(typeof (globalThis as { Worker?: unknown }).Worker).toBe('undefined');
    const v = loadVector('pow-pbkdf2.json');
    const counter = await solveInWorker('PBKDF2-SHA256', v.challenge);
    expect(counter).toBe(v.correctCounter);
  });

  it('falls back to inline when the Worker script errors (CDN/IIFE 404 path)', async () => {
    function BoomWorker(this: {
      onmessage: null;
      onerror: (() => void) | null;
      postMessage: () => void;
      terminate: () => void;
    }) {
      this.onmessage = null;
      this.onerror = null;
      this.postMessage = () => undefined;
      this.terminate = () => undefined;
      // Fire after the constructor returns so onerror is assigned by the caller.
      Promise.resolve().then(() => {
        if (this.onerror) this.onerror();
      });
    }
    vi.stubGlobal('Worker', BoomWorker as unknown as typeof Worker);

    const v = loadVector('pow-pbkdf2.json');
    const counter = await solveInWorker('PBKDF2-SHA256', v.challenge);
    expect(counter).toBe(v.correctCounter);
  });
});
