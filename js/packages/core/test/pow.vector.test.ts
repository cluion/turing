import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { describe, expect, it } from 'vitest';
import { solvePbkdf2, solveShaBit } from '../src/pow';

/**
 * Load a committed PHP fixture (the cross-language wire contract). Vite exposes
 * import.meta.url with a /@fs prefix, so strip it back to a real filesystem path.
 */
function loadVector(name: string): any {
  const url = new URL(`../../../../php/tests/vectors/${name}`, import.meta.url);
  const path = fileURLToPath(url).replace('/@fs/', '/');
  return JSON.parse(readFileSync(path, 'utf8'));
}

describe('pow vectors', () => {
  it('PBKDF2: Web Crypto reproduces PHP keySignature and finds the counter', async () => {
    const v = loadVector('pow-pbkdf2.json');
    const counter = await solvePbkdf2(v.challenge, 50);
    expect(counter).toBe(v.correctCounter);
  });

  it('SHA-bit: leading-zero-bit counting matches PHP', async () => {
    const v = loadVector('pow-shabit.json');
    const counter = await solveShaBit(v.challenge, 10);
    expect(counter).toBe(v.correctCounter);
  });
});
