import { describe, expect, it } from 'vitest';

describe('toolchain', () => {
  it('has Custom Elements', () => {
    expect(typeof customElements.define).toBe('function');
  });
});
