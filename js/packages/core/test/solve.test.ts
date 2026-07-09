import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { describe, expect, it } from 'vitest';
import { solveInWorker } from '../src/solve';

function loadVector(name: string): any {
  const url = new URL(`../../../../php/tests/vectors/${name}`, import.meta.url);
  return JSON.parse(readFileSync(fileURLToPath(url).replace('/@fs/', '/'), 'utf8'));
}

describe('solveInWorker (inline fallback)', () => {
  it('solves via inline path when Worker is unavailable', async () => {
    expect(typeof (globalThis as any).Worker).toBe('undefined');
    const v = loadVector('pow-pbkdf2.json');
    const counter = await solveInWorker('PBKDF2-SHA256', v.challenge);
    expect(counter).toBe(v.correctCounter);
  });
});
