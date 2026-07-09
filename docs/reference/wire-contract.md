# Wire contract

This is the authoritative specification every language port must reproduce. Each
section below quotes the matching fixture in `php/tests/vectors/` **verbatim** —
a docs build step (`scripts/check-vectors.mjs`) fails if the JSON here ever
drifts from the real vector files, so a porter can diff their output against
these exact bytes and trust them.

## Token layout

A token is a compact two-part string:

```
base64url(canonicalJson(payload)) . base64url(signature)
```

- **base64url** is RFC 4648 §5 without padding (`+`→`-`, `/`→`_`, no `=`).
- The signature covers the **first part's bytes** (the base64url payload
  string), not the raw JSON.
- The payload carries `v` (version), `type`, `kid` (key id), `nonce`, `iat`,
  `exp`, and a `data` object.

## Canonical JSON

The payload is serialized deterministically so every language produces identical
bytes:

- Object keys **recursively sorted** ascending by byte value.
- Slashes and Unicode **not escaped** (PHP `JSON_UNESCAPED_SLASHES |
  JSON_UNESCAPED_UNICODE`).
- No insignificant whitespace.
- Integer-like keys must keep string semantics — a port whose JSON encoder
  reorders or coerces numeric keys will not match; reject such payloads rather
  than silently reorder.

## Signing

Default is HMAC-SHA256 (HS256); Ed25519 (EdDSA, detached) is opt-in.

### HMAC-SHA256 (default)

<!-- vector:hmac-roundtrip.json -->
```json
{
  "description": "HMAC-SHA256 token round-trip. A port must produce this identical compact token from the same secret and payload (canonical JSON, sorted keys).",
  "secret": "vector-secret",
  "payload": {
    "v": 1,
    "type": "math",
    "kid": "k1",
    "nonce": "vgvectorsnonce000",
    "iat": 1000,
    "exp": 1120,
    "data": { "ah": "demo" }
  },
  "expectedCompact": "eyJkYXRhIjp7ImFoIjoiZGVtbyJ9LCJleHAiOjExMjAsImlhdCI6MTAwMCwia2lkIjoiazEiLCJub25jZSI6InZndmVjdG9yc25vbmNlMDAwIiwidHlwZSI6Im1hdGgiLCJ2IjoxfQ.hrkMQ2BPWAI3Z8fJp8XbsSLUtWfoVd0jI5hJDXTXJgo"
}
```

### Ed25519 (opt-in, `ext-sodium`)

Deterministic signatures, so the compact token is stable. Detached signature
over the canonical JSON payload.

<!-- vector:ed25519-roundtrip.json -->
```json
{
  "description": "Ed25519 token round-trip (opt-in, ext-sodium). Keypair derived from a fixed 32-byte seed (all 0x01). Ed25519 signatures are deterministic, so the compact token is stable. Detached signature over the canonical JSON payload.",
  "enabled": true,
  "seedHex": "0101010101010101010101010101010101010101010101010101010101010101",
  "publicKeyHex": "8a88e3dd7409f195fd52db2d3cba5d72ca6709bf1d94121bf3748801b40f6f5c",
  "payload": {
    "v": 1,
    "type": "pow",
    "kid": "ed",
    "nonce": "n",
    "iat": 1,
    "exp": 2,
    "data": { "ah": "demo" }
  },
  "expectedCompact": "eyJkYXRhIjp7ImFoIjoiZGVtbyJ9LCJleHAiOjIsImlhdCI6MSwia2lkIjoiZWQiLCJub25jZSI6Im4iLCJ0eXBlIjoicG93IiwidiI6MX0.JAFWow6q2xYjk1t0iiBRG5_UXj7mXCm_SA-OMgvKlqGYhd2MWyqifkz0-e_GJF8X_xO1ht40SF7uvlFCeR9SDQ"
}
```

## Proof-of-work

Default is PBKDF2-SHA256 Deterministic (the server plants a `keySignature`, the
client brute-forces the counter); SHA-256 leading-zero-bit is opt-in. All binary
payload fields are base64url of the raw bytes.

### PBKDF2-SHA256 (default)

The port derives `PBKDF2(nonce + counter, salt, cost)` and base64url-encodes it;
the result must equal `keySignature`. The smallest counter that matches is the
answer.

<!-- vector:pow-pbkdf2.json -->
```json
{
  "description": "PBKDF2-SHA256 Deterministic PoW. A port derives PBKDF2(nonce+counter, salt, cost) and base64url-encodes it; the result must equal keySignature. keySignature is base64url of the raw derived key.",
  "challenge": {
    "algorithm": "PBKDF2-SHA256",
    "salt": "vsalt",
    "nonce": "vnonce",
    "cost": 100,
    "keySignature": "5BkpOKxye8efvkkqtqzTXTKULjSJdxLtfyJW2Q29w0E"
  },
  "correctCounter": 7
}
```

### SHA-256 leading-zero-bit (opt-in)

Count the leading zero **bits** of `SHA-256(salt . counter)`; the answer is the
smallest counter whose digest has at least `difficulty_bits` leading zeros.

<!-- vector:pow-shabit.json -->
```json
{
  "description": "SHA-256 leading-zero-bit (hashcash-style) PoW. A port must reproduce identical leading-zero-bit counting of SHA-256(salt . counter).",
  "challenge": { "algorithm": "SHA-256", "salt": "vsalt", "difficulty_bits": 4 },
  "correctCounter": 0
}
```

## Single-use

Stateless by default; a `Store` makes a nonce one-shot (remembered on issue,
consumed once on verify) so a solved token cannot be replayed.
