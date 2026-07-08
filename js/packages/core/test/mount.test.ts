import { describe, expect, it, vi } from 'vitest';
import { base64UrlDecode, base64UrlEncode } from '../src/encoding';
import { autoMount, mount } from '../src/mount';

/**
 * Build a PBKDF2 challenge whose planted counter is `target`.
 */
async function powChallenge(target: number) {
  const salt = 's';
  const nonce = 'n';
  const cost = 30;
  const enc = new TextEncoder();
  const key = await crypto.subtle.importKey('raw', enc.encode(nonce + target), 'PBKDF2', false, ['deriveBits']);
  const bits = await crypto.subtle.deriveBits(
    { name: 'PBKDF2', salt: enc.encode(salt), iterations: cost, hash: 'SHA-256' },
    key,
    256,
  );
  return {
    token: 'server-token',
    image: null,
    type: 'pow',
    expires: 1,
    params: { algorithm: 'PBKDF2-SHA256', salt, nonce, cost, keySignature: base64UrlEncode(new Uint8Array(bits)) },
  };
}

describe('mount (pow)', () => {
  it('solves the counter and injects the packed token', async () => {
    const challenge = await powChallenge(4);
    vi.spyOn(globalThis, 'fetch').mockResolvedValue(new Response(JSON.stringify(challenge), { status: 200 }));

    const form = document.createElement('form');
    const el = document.createElement('div');
    el.setAttribute('data-turing', '');
    el.setAttribute('data-turing-url', 'http://localhost/turing/challenge');
    el.setAttribute('data-turing-type', 'pow');
    el.setAttribute('data-turing-field', 'turing_token');
    form.appendChild(el);
    document.body.appendChild(form);

    await mount(el);

    const input = form.querySelector<HTMLInputElement>('input[name="turing_token"]');
    expect(input).not.toBeNull();
    const unpacked = JSON.parse(new TextDecoder().decode(base64UrlDecode(input!.value)));
    expect(unpacked).toEqual({ t: 'server-token', a: '4' });
    vi.restoreAllMocks();
  });
});

describe('mount (math)', () => {
  it('renders the SVG image and injects on user input', async () => {
    const challenge = {
      token: 'tok',
      image: '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"></svg>',
      params: null,
      type: 'math',
      expires: 1,
    };
    vi.spyOn(globalThis, 'fetch').mockResolvedValue(new Response(JSON.stringify(challenge), { status: 200 }));

    const form = document.createElement('form');
    const el = document.createElement('div');
    el.setAttribute('data-turing-url', 'http://localhost/turing/challenge');
    form.appendChild(el);
    document.body.appendChild(form);

    await mount(el);

    expect(el.querySelector('svg')).not.toBeNull();
    const userInput = el.querySelector<HTMLInputElement>('input[data-turing-input]')!;
    userInput.value = '8';
    userInput.dispatchEvent(new Event('input'));

    const hidden = form.querySelector<HTMLInputElement>('input[name="turing_token"]');
    const unpacked = JSON.parse(new TextDecoder().decode(base64UrlDecode(hidden!.value)));
    expect(unpacked).toEqual({ t: 'tok', a: '8' });
    vi.restoreAllMocks();
  });

  it('strips event-handler attributes from a malicious challenge image', async () => {
    const challenge = {
      token: 'tok',
      image: '<svg xmlns="http://www.w3.org/2000/svg" onload="steal()"><foreignObject><img src="x" onerror="steal()"></foreignObject></svg>',
      params: null,
      type: 'math',
      expires: 1,
    };
    vi.spyOn(globalThis, 'fetch').mockResolvedValue(new Response(JSON.stringify(challenge), { status: 200 }));

    const form = document.createElement('form');
    const el = document.createElement('div');
    el.setAttribute('data-turing-url', 'http://localhost/turing/challenge');
    form.appendChild(el);
    document.body.appendChild(form);

    await mount(el);

    const svg = el.querySelector('svg')!;
    expect(svg.hasAttribute('onload')).toBe(false);
    expect(el.querySelector('foreignObject')).toBeNull();
    expect(el.querySelector('img')).toBeNull();
    vi.restoreAllMocks();
  });
});

describe('autoMount error handling', () => {
  it('marks the container with data-turing-state="error" on an unsupported algorithm', async () => {
    const challenge = {
      token: 'tok',
      image: null,
      type: 'pow',
      expires: 1,
      params: { algorithm: 'ROT13' },
    };
    vi.spyOn(globalThis, 'fetch').mockResolvedValue(new Response(JSON.stringify(challenge), { status: 200 }));

    const form = document.createElement('form');
    const el = document.createElement('div');
    el.setAttribute('data-turing', '');
    el.setAttribute('data-turing-url', 'http://localhost/turing/challenge');
    el.setAttribute('data-turing-type', 'pow');
    form.appendChild(el);
    document.body.appendChild(form);

    autoMount(form);
    // let the mount promise reject and the catch handler run
    await new Promise((r) => setTimeout(r, 0));

    expect(el.getAttribute('data-turing-state')).toBe('error');
    vi.restoreAllMocks();
  });
});
