import { base64UrlEncode } from './encoding';

/** The challenge envelope returned by the server endpoint. */
export interface Challenge {
  token: string;
  image: string | null;
  params: Record<string, unknown> | null;
  type: string;
  expires: number;
}

/**
 * Fetch a fresh challenge, optionally of a specific type, as JSON.
 */
export async function fetchChallenge(url: string, type?: string): Promise<Challenge> {
  const base = typeof location !== 'undefined' ? location.href : 'http://localhost';
  const target = new URL(url, base);
  if (type) {
    target.searchParams.set('type', type);
  }
  const response = await fetch(target.toString(), { headers: { Accept: 'application/json' } });
  if (!response.ok) {
    throw new Error(`challenge fetch failed: ${response.status}`);
  }
  return response.json() as Promise<Challenge>;
}

/**
 * Pack the token and answer into the single hidden-field format {t, a}.
 * The server base64url-decodes then json_decodes this, so key order is free.
 */
export function pack(token: string, answer: string): string {
  return base64UrlEncode(new TextEncoder().encode(JSON.stringify({ t: token, a: answer })));
}

/**
 * Create or update a hidden input named `field` inside `form` with `value`.
 * The lookup compares the name property directly rather than interpolating the
 * field into a CSS selector, so a field containing quotes or brackets can
 * neither break the query nor match an unintended input.
 */
export function injectToken(field: string, value: string, form: HTMLElement): void {
  let input: HTMLInputElement | undefined;
  for (const candidate of Array.from(form.querySelectorAll<HTMLInputElement>('input'))) {
    if (candidate.name === field) {
      input = candidate;
      break;
    }
  }
  if (!input) {
    input = document.createElement('input');
    input.type = 'hidden';
    input.name = field;
    form.appendChild(input);
  }
  input.value = value;
}
