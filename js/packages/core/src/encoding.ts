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

// PHP casts canonical decimal-integer string keys to real int keys, so ksort
// interleaves them differently than JS Object.keys, which always hoists such
// keys ahead of string keys in numeric order. canonicalJson rejects them rather
// than silently producing a token that a PHP verifier would compute differently.
const INTEGER_KEY = /^(?:0|-?[1-9][0-9]*)$/;

/**
 * Deterministic JSON: object keys sorted lexicographically at every depth, no
 * extra whitespace. Mirrors PHP canonicalJson (ksort + JSON_UNESCAPED_SLASHES
 * | JSON_UNESCAPED_UNICODE); non-ASCII string values are emitted raw by both
 * runtimes, so they match. Keys are assumed non-integer-like ASCII (all current
 * payload shapes are); integer-like keys throw because their PHP ordering
 * diverges. Slashes and non-ASCII are left unescaped to match PHP.
 */
export function canonicalJson(value: unknown): string {
  return JSON.stringify(sortDeep(value));
}

/**
 * Recursively return a copy with object keys sorted; arrays keep order. Uses a
 * null-prototype object so a "__proto__" key becomes an ordinary own property
 * (matching PHP) instead of reassigning the copy's prototype.
 */
function sortDeep(value: unknown): unknown {
  if (Array.isArray(value)) {
    return value.map(sortDeep);
  }
  if (value !== null && typeof value === 'object') {
    const src = value as Record<string, unknown>;
    const out: Record<string, unknown> = Object.create(null);
    for (const key of Object.keys(src).sort()) {
      if (INTEGER_KEY.test(key)) {
        throw new Error(`canonicalJson: integer-like object key "${key}" is not supported (ordering diverges from PHP)`);
      }
      out[key] = sortDeep(src[key]);
    }
    return out;
  }
  return value;
}
