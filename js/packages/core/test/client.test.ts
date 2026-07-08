import { describe, expect, it, vi } from 'vitest';
import { base64UrlDecode } from '../src/encoding';
import { fetchChallenge, injectToken, pack } from '../src/client';

describe('pack', () => {
  it('base64url-encodes {t,a} JSON the way PHP unpacks it', () => {
    const packed = pack('tok', '7');
    const json = JSON.parse(new TextDecoder().decode(base64UrlDecode(packed)));
    expect(json).toEqual({ t: 'tok', a: '7' });
  });
});

describe('fetchChallenge', () => {
  it('requests the url with the type query and returns JSON', async () => {
    const challenge = { token: 'x', image: null, params: { algorithm: 'PBKDF2-SHA256' }, type: 'pow', expires: 1 };
    const spy = vi.spyOn(globalThis, 'fetch').mockResolvedValue(
      new Response(JSON.stringify(challenge), { status: 200 }),
    );
    const out = await fetchChallenge('http://localhost/turing/challenge', 'pow');
    expect(out.type).toBe('pow');
    expect(spy.mock.calls[0][0]).toContain('type=pow');
    spy.mockRestore();
  });
});

describe('injectToken', () => {
  it('creates a hidden input and sets its value', () => {
    const form = document.createElement('form');
    injectToken('turing_token', 'packed', form);
    const input = form.querySelector<HTMLInputElement>('input[name="turing_token"]');
    expect(input?.type).toBe('hidden');
    expect(input?.value).toBe('packed');
  });
});
