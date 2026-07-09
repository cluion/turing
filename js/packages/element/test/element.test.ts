import { describe, expect, it, vi } from 'vitest';
import { base64UrlEncode } from '@cluion/turing-core';
import { TuringCaptchaElement } from '../src/element';

if (!customElements.get('turing-captcha')) {
  customElements.define('turing-captcha', TuringCaptchaElement);
}

/** A PBKDF2 challenge whose planted counter is `target`. */
async function powChallenge(target: number) {
  const enc = new TextEncoder();
  const key = await crypto.subtle.importKey('raw', enc.encode('n' + target), 'PBKDF2', false, ['deriveBits']);
  const bits = await crypto.subtle.deriveBits({ name: 'PBKDF2', salt: enc.encode('s'), iterations: 30, hash: 'SHA-256' }, key, 256);
  return { token: 'srv', image: null, type: 'pow', expires: 1,
    params: { algorithm: 'PBKDF2-SHA256', salt: 's', nonce: 'n', cost: 30, keySignature: base64UrlEncode(new Uint8Array(bits)) } };
}

describe('turing-captcha', () => {
  it('mounts on connect, injects the token into the enclosing form, emits turing:solved', async () => {
    vi.spyOn(globalThis, 'fetch').mockResolvedValue(new Response(JSON.stringify(await powChallenge(3)), { status: 200 }));
    const form = document.createElement('form');
    const el = document.createElement('turing-captcha') as TuringCaptchaElement;
    el.setAttribute('url', 'http://localhost/turing/challenge');
    el.setAttribute('type', 'pow');
    form.appendChild(el);

    const solved = new Promise<CustomEvent>((r) => el.addEventListener('turing:solved', (e) => r(e as CustomEvent)));
    document.body.appendChild(form);          // triggers connectedCallback
    await solved;

    const input = form.querySelector<HTMLInputElement>('input[name="turing_token"]');
    expect(input).not.toBeNull();
    expect(el.getAttribute('data-turing-state')).toBe('solved');
    vi.restoreAllMocks();
  });

  it('emits turing:error and sets state=error on a failed fetch', async () => {
    vi.spyOn(globalThis, 'fetch').mockResolvedValue(new Response('nope', { status: 500 }));
    const form = document.createElement('form');
    const el = document.createElement('turing-captcha') as TuringCaptchaElement;
    el.setAttribute('url', 'http://localhost/turing/challenge');
    form.appendChild(el);

    const errored = new Promise<CustomEvent>((r) => el.addEventListener('turing:error', (e) => r(e as CustomEvent)));
    document.body.appendChild(form);
    await errored;
    expect(el.getAttribute('data-turing-state')).toBe('error');
    vi.restoreAllMocks();
  });
});
