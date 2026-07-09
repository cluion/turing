import { describe, expect, it } from 'vitest';

describe('auto-register', () => {
  it('defines turing-captcha on import', async () => {
    await import('../src/index');
    expect(customElements.get('turing-captcha')).toBeTypeOf('function');
  });
});
