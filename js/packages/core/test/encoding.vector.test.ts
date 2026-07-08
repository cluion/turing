import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { describe, expect, it } from 'vitest';
import { base64UrlEncode, canonicalJson } from '../src/encoding';

/**
 * Load a committed PHP fixture (the cross-language wire contract). Vite exposes
 * import.meta.url with a /@fs prefix, so strip it back to a real filesystem path.
 */
function loadVector(name: string): any {
  const url = new URL(`../../../../php/tests/vectors/${name}`, import.meta.url);
  const path = fileURLToPath(url).replace('/@fs/', '/');
  return JSON.parse(readFileSync(path, 'utf8'));
}

describe('hmac-roundtrip vector', () => {
  it('reproduces the PHP compact token byte-for-byte', async () => {
    const v = loadVector('hmac-roundtrip.json');
    const enc = new TextEncoder();
    const json = canonicalJson(v.payload);

    const key = await crypto.subtle.importKey(
      'raw',
      enc.encode(v.secret),
      { name: 'HMAC', hash: 'SHA-256' },
      false,
      ['sign'],
    );
    const sig = new Uint8Array(await crypto.subtle.sign('HMAC', key, enc.encode(json)));

    const compact = `${base64UrlEncode(enc.encode(json))}.${base64UrlEncode(sig)}`;
    expect(compact).toBe(v.expectedCompact);
  });
});
