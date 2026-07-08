import { fetchChallenge, injectToken, pack, type Challenge } from './client';
import { solvePbkdf2, solveShaBit, type Pbkdf2Params, type ShaBitParams } from './pow';

/**
 * Mount onto a single [data-turing] container: fetch a challenge, solve PoW or
 * show the image, then inject the packed token into the enclosing form.
 */
export async function mount(el: HTMLElement): Promise<void> {
  const url = el.getAttribute('data-turing-url');
  if (!url) {
    throw new Error('data-turing-url is required');
  }
  const type = el.getAttribute('data-turing-type') ?? undefined;
  const field = el.getAttribute('data-turing-field') ?? 'turing_token';
  const form = el.closest('form') ?? el;

  const challenge = await fetchChallenge(url, type);

  if (challenge.type === 'pow' && challenge.params) {
    const counter = await solvePow(challenge.params);
    injectToken(field, pack(challenge.token, String(counter)), form as HTMLElement);
    el.setAttribute('data-turing-state', 'solved');
    return;
  }

  if (challenge.image) {
    renderImageChallenge(el, form as HTMLElement, field, challenge);
  }
}

/**
 * Scan the DOM for [data-turing] containers and mount each one.
 */
export function autoMount(root: ParentNode = document): void {
  root.querySelectorAll<HTMLElement>('[data-turing]').forEach((el) => {
    void mount(el);
  });
}

/**
 * Dispatch to the correct PoW solver based on the advertised algorithm.
 */
function solvePow(params: Record<string, unknown>): Promise<number> {
  return params.algorithm === 'SHA-256'
    ? solveShaBit(params as unknown as ShaBitParams)
    : solvePbkdf2(params as unknown as Pbkdf2Params);
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
    el.appendChild(document.importNode(svg, true));
  }

  const input = document.createElement('input');
  input.type = 'text';
  input.setAttribute('data-turing-input', '');
  el.appendChild(input);

  input.addEventListener('input', () => {
    injectToken(field, pack(challenge.token, input.value), form);
  });
}
