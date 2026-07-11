import { fetchChallenge, injectToken, pack, type Challenge } from './client';
import { solvePbkdf2, solveShaBit, type Pbkdf2Params, type ShaBitParams } from './pow';
import { solveInWorker } from './solve';
import { sanitizeSvg } from './svg-sanitize';

/** Options for a single mount: an AbortSignal to cancel the solve. */
export interface MountOptions {
  signal?: AbortSignal;
}

/** User-facing strings for the PoW widget; all overridable via data-* attributes. */
export interface TuringLabels {
  loading: string;
  idle: string;
  solving: string;
  solved: string;
  error: string;
  /** Accessible name for the checkbox. */
  aria: string;
  /** Math/text refresh control. */
  refresh: string;
}

export const DEFAULT_LABELS: TuringLabels = {
  loading: 'Loading…',
  idle: "I'm not a robot",
  solving: 'Verifying…',
  solved: 'Verified',
  error: 'Verification failed — try again',
  aria: 'Verify you are human',
  refresh: 'Refresh',
};

/**
 * Read label overrides from the mount element.
 * - `data-turing-label` is a shorthand for the idle (checkbox) line.
 * - `data-turing-label-{loading,idle,solving,solved,error,aria}` override each phase.
 * Specific keys win over the idle shorthand.
 */
export function readLabels(el: HTMLElement): TuringLabels {
  const shorthand = el.getAttribute('data-turing-label');
  const pick = (key: keyof TuringLabels, attr: string): string => {
    const v = el.getAttribute(attr);
    if (v !== null && v !== '') return v;
    if (key === 'idle' && shorthand !== null && shorthand !== '') return shorthand;
    return DEFAULT_LABELS[key];
  };
  return {
    loading: pick('loading', 'data-turing-label-loading'),
    idle: pick('idle', 'data-turing-label-idle'),
    solving: pick('solving', 'data-turing-label-solving'),
    solved: pick('solved', 'data-turing-label-solved'),
    error: pick('error', 'data-turing-label-error'),
    aria: pick('aria', 'data-turing-label-aria'),
    refresh: pick('refresh', 'data-turing-label-refresh'),
  };
}

/**
 * Mount onto a single [data-turing] container: fetch a challenge, solve PoW or
 * show the image, then inject the packed token into the enclosing form.
 *
 * PoW defaults to an interactive checkbox UI (idle → solving → solved). Pass
 * `data-turing-autostart` to solve immediately without a click. Web Workers are
 * on by default for PoW; set `data-turing-no-worker` to force the main thread.
 * Status copy is overridable via `data-turing-label*` attributes.
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
  const labels = readLabels(el);
  const enclosingForm = el.closest('form');
  if (!enclosingForm) {
    // Without an enclosing <form> the hidden token input is never submitted, so
    // the widget would silently produce nothing. Surface it instead of hiding it.
    console.warn('turing: [data-turing] is not inside a <form>; the token will not be submitted.');
  }
  const form = enclosingForm ?? el;

  setState(el, 'loading');
  setStatusLabel(el, labels.loading);

  const challenge = await fetchChallenge(url, type);

  if (challenge.type === 'pow' && challenge.params) {
    if (autostart) {
      await runPowSolve(el, form as HTMLElement, field, challenge, useWorker, labels, opts.signal);
      return;
    }
    // Interactive: build the checkbox UI and return; solve starts on user action.
    renderPowIdle(el, form as HTMLElement, field, challenge, useWorker, labels, opts.signal);
    return;
  }

  if (challenge.image) {
    paintImageChallenge(el, form as HTMLElement, field, challenge, labels, type);
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
  labels: TuringLabels,
  signal?: AbortSignal,
): Promise<void> {
  setState(el, 'solving');
  setStatusLabel(el, labels.solving);
  lockCheckbox(el, true, true);
  try {
    const counter = await solvePow(challenge.params as Record<string, unknown>, useWorker, signal);
    injectToken(field, pack(challenge.token, String(counter)), form);
    setState(el, 'solved');
    setStatusLabel(el, labels.solved);
    lockCheckbox(el, true, true);
    el.dispatchEvent(new CustomEvent('turing:solved', { bubbles: true }));
  } catch (error: unknown) {
    markError(el, error, labels);
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
  labels: TuringLabels,
  signal?: AbortSignal,
): void {
  el.replaceChildren();
  setState(el, 'idle');

  const widget = document.createElement('div');
  widget.setAttribute('data-turing-widget', '');

  const labelEl = document.createElement('label');
  labelEl.setAttribute('data-turing-label', '');

  const check = document.createElement('input');
  check.type = 'checkbox';
  check.setAttribute('data-turing-check', '');
  check.setAttribute('aria-label', labels.aria);

  const status = document.createElement('span');
  status.setAttribute('data-turing-status', '');
  status.textContent = labels.idle;

  labelEl.append(check, status);
  widget.appendChild(labelEl);
  el.appendChild(widget);

  let running = false;
  check.addEventListener('change', () => {
    if (!check.checked || running) {
      // Do not allow unchecking after start; restore checked if they try mid-solve.
      if (running) check.checked = true;
      return;
    }
    running = true;
    void runPowSolve(el, form, field, challenge, useWorker, labels, signal).catch(() => {
      running = false;
      // Offer retry: unlock checkbox, keep error state label until next attempt.
      const retry = el.querySelector<HTMLInputElement>('[data-turing-check]');
      if (retry) {
        retry.disabled = false;
        retry.checked = false;
      }
      // Re-fetch a fresh challenge so a spent/expired token cannot be reused.
      void fetchChallenge(
        el.getAttribute('data-turing-url') as string,
        el.getAttribute('data-turing-type') ?? undefined,
      )
        .then((fresh) => {
          if (fresh.type === 'pow' && fresh.params) {
            Object.assign(challenge, fresh);
            setState(el, 'idle');
            setStatusLabel(el, labels.idle);
          }
        })
        .catch(() => {
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
 * Paint an image challenge (with optional refresh) and mark ready.
 */
function paintImageChallenge(
  el: HTMLElement,
  form: HTMLElement,
  field: string,
  challenge: Challenge,
  labels: TuringLabels,
  type: string | undefined,
): void {
  renderImageShell(el, form, field, challenge, labels, () => {
    const url = el.getAttribute('data-turing-url');
    if (!url) return;
    setState(el, 'loading');
    setStatusLabel(el, labels.loading);
    clearPackedToken(field, form);
    void fetchChallenge(url, type ?? challenge.type)
      .then((fresh) => {
        if (!fresh.image) {
          markError(el, new Error('refresh did not return an image challenge'), labels);
          return;
        }
        paintImageChallenge(el, form, field, fresh, labels, type);
      })
      .catch((error: unknown) => markError(el, error, labels));
  });
  setState(el, 'ready');
  el.dispatchEvent(new CustomEvent('turing:ready', { bubbles: true }));
}

/**
 * Render the SVG image (via DOMParser, never innerHTML) plus answer input and
 * optional refresh control. No innerHTML — Trusted Types / strict CSP safe.
 */
function renderImageShell(
  el: HTMLElement,
  form: HTMLElement,
  field: string,
  challenge: Challenge,
  labels: TuringLabels,
  onRefresh: () => void,
): void {
  el.replaceChildren();
  const row = document.createElement('div');
  row.setAttribute('data-turing-image-row', '');

  const parsed = new DOMParser().parseFromString(challenge.image as string, 'text/html');
  const svg = parsed.querySelector('svg');
  if (svg) {
    const imported = document.importNode(svg, true) as Element;
    sanitizeSvg(imported);
    row.appendChild(imported);
  }

  if (!el.hasAttribute('data-turing-no-refresh')) {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.setAttribute('data-turing-refresh', '');
    btn.textContent = labels.refresh;
    btn.setAttribute('aria-label', labels.refresh);
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      onRefresh();
    });
    row.appendChild(btn);
  }
  el.appendChild(row);

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
    if (input.value.length > 0) {
      el.dispatchEvent(new CustomEvent('turing:solved', { bubbles: true }));
    }
  });
}

/** Clear a previously injected packed token after refresh. */
function clearPackedToken(field: string, form: HTMLElement): void {
  for (const candidate of Array.from(form.querySelectorAll<HTMLInputElement>('input'))) {
    if (candidate.name === field) {
      candidate.value = '';
      break;
    }
  }
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

function markError(el: HTMLElement, error: unknown, labels?: TuringLabels): void {
  const copy = labels ?? readLabels(el);
  setState(el, 'error');
  setStatusLabel(el, copy.error);
  lockCheckbox(el, false, false);
  el.dispatchEvent(new CustomEvent('turing:error', { bubbles: true, detail: { error } }));
}
