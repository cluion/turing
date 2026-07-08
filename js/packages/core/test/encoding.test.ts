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

  it('leaves slashes and non-ASCII unescaped to match PHP', () => {
    expect(canonicalJson({ a: 'x/y', b: 'café' })).toBe('{"a":"x/y","b":"café"}');
  });

  it('rejects integer-like keys whose PHP ordering would diverge', () => {
    expect(() => canonicalJson({ '0': 'a' })).toThrow(/integer-like/);
    expect(() => canonicalJson({ '10': 'a', '2': 'b' })).toThrow(/integer-like/);
  });

  it('treats a __proto__ key as ordinary data without polluting prototypes', () => {
    const parsed = JSON.parse('{"__proto__":{"x":1},"a":2}');
    expect(canonicalJson(parsed)).toBe('{"__proto__":{"x":1},"a":2}');
    expect(({} as Record<string, unknown>).x).toBeUndefined();
  });
});
