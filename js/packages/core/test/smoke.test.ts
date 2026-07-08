import { describe, expect, it } from 'vitest';

describe('toolchain', () => {
  it('runs vitest and exposes Web Crypto', () => {
    expect(typeof crypto.subtle.deriveBits).toBe('function');
  });
});
