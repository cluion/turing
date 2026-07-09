import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { describe, expect, it } from 'vitest';
import { handleRequest } from '../src/worker-protocol';

function loadVector(name: string): any {
  const url = new URL(`../../../../php/tests/vectors/${name}`, import.meta.url);
  return JSON.parse(readFileSync(fileURLToPath(url).replace('/@fs/', '/'), 'utf8'));
}

describe('handleRequest', () => {
  it('solves the PBKDF2 vector', async () => {
    const v = loadVector('pow-pbkdf2.json');
    const res = await handleRequest({ algorithm: 'PBKDF2-SHA256', params: v.challenge });
    expect(res).toEqual({ ok: true, counter: v.correctCounter });
  });

  it('reports an error response for an unsupported algorithm', async () => {
    const res = await handleRequest({ algorithm: 'ROT13', params: {} });
    expect(res.ok).toBe(false);
  });
});
