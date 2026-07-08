/**
 * URL-safe base64 without padding. Mirrors PHP TokenEncoder::base64UrlEncode.
 */
export function base64UrlEncode(bytes: Uint8Array): string {
  let bin = '';
  for (let i = 0; i < bytes.length; i++) {
    bin += String.fromCharCode(bytes[i]);
  }
  return btoa(bin).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
}

/**
 * Decode URL-safe base64 back to bytes. Mirrors PHP TokenEncoder::base64UrlDecode.
 */
export function base64UrlDecode(s: string): Uint8Array {
  const pad = s.length % 4;
  const b64 = s.replace(/-/g, '+').replace(/_/g, '/') + (pad ? '='.repeat(4 - pad) : '');
  const bin = atob(b64);
  const out = new Uint8Array(bin.length);
  for (let i = 0; i < bin.length; i++) {
    out[i] = bin.charCodeAt(i);
  }
  return out;
}

/**
 * Deterministic JSON: keys sorted lexicographically at every depth, no extra
 * whitespace. Mirrors PHP canonicalJson (ksort + JSON_UNESCAPED_SLASHES|UNICODE).
 * The sort must match PHP for pure-ASCII keys, which both compare as strings.
 */
export function canonicalJson(value: unknown): string {
  return JSON.stringify(sortDeep(value));
}

/**
 * Recursively return a copy with object keys sorted; arrays keep order.
 */
function sortDeep(value: unknown): unknown {
  if (Array.isArray(value)) {
    return value.map(sortDeep);
  }
  if (value !== null && typeof value === 'object') {
    const src = value as Record<string, unknown>;
    const out: Record<string, unknown> = {};
    for (const key of Object.keys(src).sort()) {
      out[key] = sortDeep(src[key]);
    }
    return out;
  }
  return value;
}
