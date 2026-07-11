import { fetchChallenge, injectToken, pack, type Challenge } from './client';
import { solvePbkdf2, solveShaBit, type Pbkdf2Params, type ShaBitParams } from './pow';
import { solveInWorker } from './solve';
import { sanitizeSvg } from './svg-sanitize';

/** Options for a single mount: an AbortSignal to cancel the solve. */
export interface MountOptions {
  signal?: AbortSignal;
}

/**
 * Mount onto a single [data-turing] container: fetch a challenge, solve PoW or
 * show the image, then inject the packed token into the enclosing form. Opt into
 * offloading the PoW to a Web Worker with the `data-turing-worker` attribute;
 * pass an AbortSignal via opts to cancel an in-flight solve.
 */
export async function mount(el: HTMLElement, opts: MountOptions = {}): Promise<void> {
  const url = el.getAttribute('data-turing-url');
  if (!url) {
    throw new Error('data-turing-url is required');
  }
  const type = el.getAttribute('data-turing-type') ?? undefined;
  const useWorker = el.hasAttribute('data-turing-worker');
  // Fall back on an empty attribute too, so data-turing-field="" cannot target
  // a nameless input; the '' name is never what an integrator intends.
  const field = el.getAttribute('data-turing-field') || 'turing_token';
  const enclosingForm = el.closest('form');
  if (!enclosingForm) {
    // Without an enclosing <form> the hidden token input is never submitted, so
    // the widget would silently produce nothing. Surface it instead of hiding it.
    console.warn('turing: [data-turing] is not inside a <form>; the token will not be submitted.');
  }
  const form = enclosingForm ?? el;

  el.setAttribute('data-turing-state', 'loading');
  const challenge = await fetchChallenge(url, type);

  if (challenge.type === 'pow' && challenge.params) {
    // PoW has no image — surface progress so the mount is not an empty box.
    setStatus(el, 'solving', 'Verifying…');
    const counter = await solvePow(challenge.params, useWorker, opts.signal);
    injectToken(field, pack(challenge.token, String(counter)), form as HTMLElement);
    setStatus(el, 'solved', 'Verified');
    return;
  }

  if (challenge.image) {
    renderImageChallenge(el, form as HTMLElement, field, challenge);
    el.setAttribute('data-turing-state', 'ready');
  }
}

/**
 * Replace the container contents with a short status line and stamp
 * data-turing-state so host CSS can style solving / solved / error.
 */
function setStatus(el: HTMLElement, state: string, label: string): void {
  el.setAttribute('data-turing-state', state);
  el.replaceChildren();
  const status = document.createElement('span');
  status.setAttribute('data-turing-status', '');
  status.textContent = label;
  el.appendChild(status);
}

/**
 * Scan the DOM for [data-turing] containers and mount each one. A mount failure
 * (bad config, failed fetch, unsolved PoW) marks the container with
 * data-turing-state="error" and dispatches a bubbling turing:error event rather
 * than surfacing only as an unhandled promise rejection.
 */
export function autoMount(root: ParentNode = document): void {
  root.querySelectorAll<HTMLElement>('[data-turing]').forEach((el) => {
    mount(el).catch((error: unknown) => {
      el.setAttribute('data-turing-state', 'error');
      el.dispatchEvent(new CustomEvent('turing:error', { bubbles: true, detail: { error } }));
    });
  });
}

/**
 * Dispatch to the correct PoW solver based on the advertised algorithm, failing
 * loudly on anything the server would not have issued (mirrors PHP
 * PowType::solverFor throwing PowAlgorithmUnsupported). When useWorker is set,
 * offload to a Web Worker (which falls back to inline when Worker is
 * unavailable); otherwise solve inline on the current thread.
 */
function solvePow(params: Record<string, unknown>, useWorker: boolean, signal?: AbortSignal): Promise<number> {
  const algorithm = String(params.algorithm);
  if (algorithm !== 'PBKDF2-SHA256' && algorithm !== 'SHA-256') {
    return Promise.reject(new Error(`Unsupported PoW algorithm: ${algorithm}`));
  }
  if (useWorker) {
    return solveInWorker(algorithm, params, { signal });
  }
  if (algorithm === 'PBKDF2-SHA256') {
    return solvePbkdf2(params as unknown as Pbkdf2Params, undefined, undefined, signal);
  }
  return solveShaBit(params as unknown as ShaBitParams, undefined, undefined, signal);
}

/**
 * Render the SVG image (via DOMParser, never innerHTML) and inject the packed
 * token whenever the user types an answer. The markup is parsed as text/html so
 * the HTML parser handles the inline SVG as foreign content, then the node is
 * imported into this document and appended -- no innerHTML string assignment,
 * so Trusted Types and strict CSP stay satisfied.
 */
function renderImageChallenge(el: HTMLElement, form: HTMLElement, field: string, challenge: Challenge): void {
  el.replaceChildren();
  const parsed = new DOMParser().parseFromString(challenge.image as string, 'text/html');
  const svg = parsed.querySelector('svg');
  if (svg) {
    const imported = document.importNode(svg, true) as Element;
    sanitizeSvg(imported);
    el.appendChild(imported);
  }

  const input = document.createElement('input');
  input.type = 'text';
  input.setAttribute('data-turing-input', '');
  el.appendChild(input);

  input.addEventListener('input', () => {
    injectToken(field, pack(challenge.token, input.value), form);
  });
}
