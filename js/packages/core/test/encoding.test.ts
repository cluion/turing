import { describe, expect, it } from 'vitest';
import { base64UrlDecode, base64UrlEncode, canonicalJson } from '../src/encoding';

describe('base64url', () => {
  it('round-trips arbitrary bytes', () => {
    const bytes = new Uint8Array([0, 1, 250, 251, 255, 42]);
    expect(base64UrlDecode(base64UrlEncode(bytes))).toEqual(bytes);
  });

  it('has no +, / or = characters', () => {
    expect(base64UrlEncode(new Uint8Array([255, 254, 251]))).not.toMatch(/[+/=]/);
  });
});

describe('canonicalJson', () => {
  it('sorts keys at every depth', () => {
    const out = canonicalJson({ b: 1, a: { y: 2, x: 1 }, c: 3 });
    expect(out).toBe('{"a":{"x":1,"y":2},"b":1,"c":3}');
  });
});
