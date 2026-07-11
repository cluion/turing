import { describe, expect, it, vi } from 'vitest';
import { base64UrlDecode, base64UrlEncode } from '../src/encoding';
import { autoMount, mount } from '../src/mount';
import * as solveModule from '../src/solve';

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

function powMountEl(attrs: Record<string, string> = {}): { form: HTMLFormElement; el: HTMLDivElement } {
  const form = document.createElement('form');
  const el = document.createElement('div');
  el.setAttribute('data-turing', '');
  el.setAttribute('data-turing-url', 'http://localhost/turing/challenge');
  el.setAttribute('data-turing-type', 'pow');
  el.setAttribute('data-turing-field', 'turing_token');
  for (const [k, v] of Object.entries(attrs)) el.setAttribute(k, v);
  form.appendChild(el);
  document.body.appendChild(form);
  return { form, el };
}

describe('mount (pow)', () => {
  it('interactive: checkbox starts solve, injects token, emits turing:solved', async () => {
    const challenge = await powChallenge(4);
    vi.spyOn(globalThis, 'fetch').mockResolvedValue(new Response(JSON.stringify(challenge), { status: 200 }));
    const { form, el } = powMountEl({ 'data-turing-no-worker': '' });

    await mount(el);
    expect(el.getAttribute('data-turing-state')).toBe('idle');
    expect(el.querySelector('[data-turing-check]')).not.toBeNull();

    const solved = new Promise<void>((r) => el.addEventListener('turing:solved', () => r(), { once: true }));
    const check = el.querySelector<HTMLInputElement>('[data-turing-check]')!;
    check.checked = true;
    check.dispatchEvent(new Event('change'));
    await solved;

    const input = form.querySelector<HTMLInputElement>('input[name="turing_token"]');
    expect(input).not.toBeNull();
    const unpacked = JSON.parse(new TextDecoder().decode(base64UrlDecode(input!.value)));
    expect(unpacked).toEqual({ t: 'server-token', a: '4' });
    expect(el.getAttribute('data-turing-state')).toBe('solved');
    expect(el.querySelector('[data-turing-status]')?.textContent).toBe('Verified');
    vi.restoreAllMocks();
  });

  it('autostart: solves immediately without a click', async () => {
    const challenge = await powChallenge(4);
    vi.spyOn(globalThis, 'fetch').mockResolvedValue(new Response(JSON.stringify(challenge), { status: 200 }));
    const { form, el } = powMountEl({
      'data-turing-autostart': '',
      'data-turing-no-worker': '',
    });

    await mount(el);

    const input = form.querySelector<HTMLInputElement>('input[name="turing_token"]');
    const unpacked = JSON.parse(new TextDecoder().decode(base64UrlDecode(input!.value)));
    expect(unpacked).toEqual({ t: 'server-token', a: '4' });
    expect(el.getAttribute('data-turing-state')).toBe('solved');
    vi.restoreAllMocks();
  });

  it('uses custom labels from data-turing-label* attributes', async () => {
    const challenge = await powChallenge(4);
    vi.spyOn(globalThis, 'fetch').mockResolvedValue(new Response(JSON.stringify(challenge), { status: 200 }));
    const { el } = powMountEl({
      'data-turing-no-worker': '',
      'data-turing-label': '我不是機器人',
      'data-turing-label-solving': '驗證中…',
      'data-turing-label-solved': '已通過',
    });

    await mount(el);
    expect(el.querySelector('[data-turing-status]')?.textContent).toBe('我不是機器人');

    const solved = new Promise<void>((r) => el.addEventListener('turing:solved', () => r(), { once: true }));
    const check = el.querySelector<HTMLInputElement>('[data-turing-check]')!;
    check.checked = true;
    check.dispatchEvent(new Event('change'));
    await solved;

    expect(el.querySelector('[data-turing-status]')?.textContent).toBe('已通過');
    vi.restoreAllMocks();
  });

  it('uses solveInWorker by default (inline fallback when Worker is missing)', async () => {
    expect(typeof (globalThis as unknown as { Worker?: unknown }).Worker).toBe('undefined');
    const challenge = await powChallenge(4);
    vi.spyOn(globalThis, 'fetch').mockResolvedValue(new Response(JSON.stringify(challenge), { status: 200 }));
    const worker = vi.spyOn(solveModule, 'solveInWorker');
    const { el } = powMountEl({ 'data-turing-autostart': '' });

    await mount(el);

    expect(worker).toHaveBeenCalledWith('PBKDF2-SHA256', expect.anything(), expect.anything());
    expect(el.getAttribute('data-turing-state')).toBe('solved');
    vi.restoreAllMocks();
  });

  it('skips the worker when data-turing-no-worker is set', async () => {
    const challenge = await powChallenge(4);
    vi.spyOn(globalThis, 'fetch').mockResolvedValue(new Response(JSON.stringify(challenge), { status: 200 }));
    const worker = vi.spyOn(solveModule, 'solveInWorker');
    const { el } = powMountEl({
      'data-turing-autostart': '',
      'data-turing-no-worker': '',
    });

    await mount(el);

    expect(worker).not.toHaveBeenCalled();
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
    expect(el.getAttribute('data-turing-state')).toBe('ready');
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
    el.setAttribute('data-turing-autostart', '');
    form.appendChild(el);
    document.body.appendChild(form);

    autoMount(form);
    // let the mount promise reject and the catch handler run
    await new Promise((r) => setTimeout(r, 0));

    expect(el.getAttribute('data-turing-state')).toBe('error');
    vi.restoreAllMocks();
  });
});
