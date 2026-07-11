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
 * show the image, then inject the packed token into the enclosing form.
 *
 * PoW defaults to an interactive checkbox UI (idle → solving → solved). Pass
 * `data-turing-autostart` to solve immediately without a click. Web Workers are
 * on by default for PoW; set `data-turing-no-worker` to force the main thread.
 */
export async function mount(el: HTMLElement, opts: MountOptions = {}): Promise<void> {
  const url = el.getAttribute('data-turing-url');
  if (!url) {
    throw new Error('data-turing-url is required');
  }
  const type = el.getAttribute('data-turing-type') ?? undefined;
  // Worker on by default; opt out with data-turing-no-worker.
  const useWorker = !el.hasAttribute('data-turing-no-worker');
  const autostart = el.hasAttribute('data-turing-autostart');
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

  setState(el, 'loading');
  setStatusLabel(el, 'Loading…');

  const challenge = await fetchChallenge(url, type);

  if (challenge.type === 'pow' && challenge.params) {
    if (autostart) {
      await runPowSolve(el, form as HTMLElement, field, challenge, useWorker, opts.signal);
      return;
    }
    // Interactive: build the checkbox UI and return; solve starts on user action.
    renderPowIdle(el, form as HTMLElement, field, challenge, useWorker, opts.signal);
    return;
  }

  if (challenge.image) {
    renderImageChallenge(el, form as HTMLElement, field, challenge);
    setState(el, 'ready');
    el.dispatchEvent(new CustomEvent('turing:ready', { bubbles: true }));
  }
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
      markError(el, error);
    });
  });
}

/**
 * Run the PoW loop, inject the token, and emit turing:solved.
 */
async function runPowSolve(
  el: HTMLElement,
  form: HTMLElement,
  field: string,
  challenge: Challenge,
  useWorker: boolean,
  signal?: AbortSignal,
): Promise<void> {
  setState(el, 'solving');
  setStatusLabel(el, 'Verifying…');
  lockCheckbox(el, true, true);
  try {
    const counter = await solvePow(challenge.params as Record<string, unknown>, useWorker, signal);
    injectToken(field, pack(challenge.token, String(counter)), form);
    setState(el, 'solved');
    setStatusLabel(el, 'Verified');
    lockCheckbox(el, true, true);
    el.dispatchEvent(new CustomEvent('turing:solved', { bubbles: true }));
  } catch (error: unknown) {
    markError(el, error);
    throw error;
  }
}

/**
 * Interactive PoW panel: checkbox + status. Checking the box starts the solve.
 * Structure only (no inline styles) so strict CSP stays happy.
 */
function renderPowIdle(
  el: HTMLElement,
  form: HTMLElement,
  field: string,
  challenge: Challenge,
  useWorker: boolean,
  signal?: AbortSignal,
): void {
  el.replaceChildren();
  setState(el, 'idle');

  const widget = document.createElement('div');
  widget.setAttribute('data-turing-widget', '');

  const label = document.createElement('label');
  label.setAttribute('data-turing-label', '');

  const check = document.createElement('input');
  check.type = 'checkbox';
  check.setAttribute('data-turing-check', '');
  check.setAttribute('aria-label', 'Verify you are human');

  const status = document.createElement('span');
  status.setAttribute('data-turing-status', '');
  status.textContent = "I'm not a robot";

  label.append(check, status);
  widget.appendChild(label);
  el.appendChild(widget);

  let running = false;
  check.addEventListener('change', () => {
    if (!check.checked || running) {
      // Do not allow unchecking after start; restore checked if they try mid-solve.
      if (running) check.checked = true;
      return;
    }
    running = true;
    void runPowSolve(el, form, field, challenge, useWorker, signal).catch(() => {
      running = false;
      // Offer retry: unlock checkbox, keep error state label until next attempt.
      const retry = el.querySelector<HTMLInputElement>('[data-turing-check]');
      if (retry) {
        retry.disabled = false;
        retry.checked = false;
      }
      // Re-bind is unnecessary; change handler stays. Re-fetch a fresh challenge
      // on retry so a spent/expired token cannot be reused after a failed attempt.
      void fetchChallenge(
        el.getAttribute('data-turing-url') as string,
        el.getAttribute('data-turing-type') ?? undefined,
      ).then((fresh) => {
        if (fresh.type === 'pow' && fresh.params) {
          Object.assign(challenge, fresh);
          setState(el, 'idle');
          setStatusLabel(el, "I'm not a robot");
        }
      }).catch(() => {
        /* keep error label from markError */
      });
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
  input.setAttribute('autocomplete', 'off');
  input.setAttribute('autocapitalize', 'characters');
  input.setAttribute('spellcheck', 'false');
  input.setAttribute('aria-label', 'Captcha answer');
  el.appendChild(input);

  input.addEventListener('input', () => {
    injectToken(field, pack(challenge.token, input.value), form);
    // Emit solved when the user has typed something; server still validates.
    if (input.value.length > 0) {
      el.dispatchEvent(new CustomEvent('turing:solved', { bubbles: true }));
    }
  });
}

function setState(el: HTMLElement, state: string): void {
  el.setAttribute('data-turing-state', state);
}

/**
 * Ensure a [data-turing-status] node exists and set its text. Builds a minimal
 * widget shell when the status span is missing (e.g. first loading paint).
 */
function setStatusLabel(el: HTMLElement, label: string): void {
  let status = el.querySelector<HTMLElement>('[data-turing-status]');
  if (!status) {
    el.replaceChildren();
    const widget = document.createElement('div');
    widget.setAttribute('data-turing-widget', '');
    const wrap = document.createElement('label');
    wrap.setAttribute('data-turing-label', '');
    status = document.createElement('span');
    status.setAttribute('data-turing-status', '');
    wrap.appendChild(status);
    widget.appendChild(wrap);
    el.appendChild(widget);
  }
  status.textContent = label;
}

function lockCheckbox(el: HTMLElement, checked: boolean, disabled: boolean): void {
  const check = el.querySelector<HTMLInputElement>('[data-turing-check]');
  if (!check) return;
  check.checked = checked;
  check.disabled = disabled;
}

function markError(el: HTMLElement, error: unknown): void {
  setState(el, 'error');
  setStatusLabel(el, 'Verification failed — try again');
  lockCheckbox(el, false, false);
  el.dispatchEvent(new CustomEvent('turing:error', { bubbles: true, detail: { error } }));
}
