# Security

Turing is self-hosted. The server issues and verifies challenges; nothing is
sent to a third-party captcha service.

## Types

| Type | What it is | Fit |
|------|------------|-----|
| `pow` | Browser spends CPU (PBKDF2 or SHA-bit) before the form can pass | Default for public or high-traffic forms |
| `math` / `text` | User solves a short visual challenge | Light friction only; not a strong barrier alone |

PoW makes bulk automation more expensive. It does not identify a person. Math
and text avoid trivial DOM text scraping; determined clients can still automate
them. Prefer `pow` when the form is worth attacking.

Difficulty bands for PoW: `interactive`, `balanced`, `strict` (see the Laravel
guide / `TURING_POW_PROFILE`).

## Built-in server controls

| Control | Laravel default |
|---------|-----------------|
| Token lifetime | ~120s (`exp`) |
| Single-use nonce | `store=cache` |
| Challenge endpoint rate limit | `throttle:60,1` |
| Signing secret | `TURING_SECRET` (required) |

Core can run without a store (`null`). Laravel defaults to cache so a solved
token cannot be replayed across requests.

## Your application still owns

- Rate limits on login, register, and other sensitive POSTs  
- HTTPS and a long, private `TURING_SECRET`  
- Product risk rules (accounts, abuse patterns, etc.)  

Failed checks can be logged with codes such as `expired`, `already_used`, or
`wrong_answer` for metrics. Do not show those codes to end users.

## CSP

The Blade mount emits no inline script. Default PoW uses Web Crypto only (no
WASM). Load the client from your origin or a version-pinned CDN with SRI.
