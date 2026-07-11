# Turing

[![CI](https://github.com/cluion/turing/actions/workflows/ci.yml/badge.svg)](https://github.com/cluion/turing/actions/workflows/ci.yml)
[![npm](https://img.shields.io/npm/v/@cluion/turing-core?label=npm%20%40cluion%2Fturing-core)](https://www.npmjs.com/package/@cluion/turing-core)
[![Packagist](https://img.shields.io/packagist/v/cluion/turing?label=packagist%20cluion%2Fturing)](https://packagist.org/packages/cluion/turing)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](#license)

**English** · [繁體中文](README.zh-TW.md) · 📖 [Documentation](https://cluion.github.io/turing/)

A signed challenge the server issues, a client widget that solves it in the
browser with native Web Crypto, and a token the enclosing form submits for the
server to verify. No external service, no tracking. Three layers: a
framework-agnostic **PHP Core**, framework **integrations** (Laravel first), and
a **JS client** stack.

## Install

### Laravel (PHP)

```bash
composer require cluion/turing
```

```bash
php artisan vendor:publish --tag=turing-config   # then set TURING_SECRET
```

### JavaScript

| Package | Install | Use |
|---------|---------|-----|
| [`@cluion/turing-core`](https://www.npmjs.com/package/@cluion/turing-core) | `pnpm add @cluion/turing-core` | Headless core (plain / any framework) |
| [`@cluion/turing-element`](https://www.npmjs.com/package/@cluion/turing-element) | `pnpm add @cluion/turing-element` | `<turing-captcha>` Web Component |
| [`@cluion/turing-vue`](https://www.npmjs.com/package/@cluion/turing-vue) | `pnpm add @cluion/turing-vue` | `<Turing>` Vue 3 component |
| [`@cluion/turing-react`](https://www.npmjs.com/package/@cluion/turing-react) | `pnpm add @cluion/turing-react` | `<Turing/>` React component |

Plain HTML via CDN — pin an exact version and add Subresource Integrity:

```html
<script
  src="https://cdn.jsdelivr.net/npm/@cluion/turing-core@0.1.2/dist/turing.global.js"
  integrity="sha384-aW7oySKgbHetDA/gXWt03vSQLONWdwQ1/L97BQaeN7E1IQkMcXsC0xTt0cvAjZZy"
  crossorigin="anonymous"
  defer></script>
```

## Usage

### Laravel — one line to show, one line to verify

```blade
<form method="post" action="/submit">
  @csrf
  <x-turing type="pow" />
  <button type="submit">Send</button>
</form>
```

```php
$request->validate(['turing_token' => 'required|turing']);
// or: Turing::verifyRequest($request);
```

The `<x-turing/>` component renders a CSP-safe container the client widget mounts
onto. The widget fetches the challenge from `data-turing-url`, solves the PoW
with native Web Crypto (no WASM), and injects a hidden `turing_token` for the
form to submit.

### JavaScript

```js
import '@cluion/turing-core'; // auto-mounts every [data-turing] container
```

A runnable plain-HTML page is in
[`examples/plain-html/index.html`](examples/plain-html/index.html). Framework
guides: [Laravel](docs/guide/laravel.md) · [Plain HTML](docs/guide/plain-html.md)
· [Vue](docs/guide/vue.md) · [React](docs/guide/react.md).

## Core

Framework-agnostic PHP core under `php/src/Core`. Token =
`base64url(payload).base64url(signature)` with canonical (sorted-key) JSON.
HMAC-SHA256 default signing (Ed25519 opt-in). Challenge types: `math`, `text`,
`pow` (PBKDF2-SHA256 default, SHA-256 leading-zero-bit opt-in). Stateless by
default; single-use via a `Store`.

## Cross-language vectors

[`php/tests/vectors/`](php/tests/vectors/) is the wire contract — the
authoritative [wire-contract reference](docs/reference/wire-contract.md) quotes
them verbatim (a docs build guard fails on drift). Language ports MUST reproduce
these fixtures exactly (token bytes, PoW counters, answer hashes). Binary payload
fields (e.g. `keySignature`) are always base64url of the raw bytes.

## Development

```bash
composer install
vendor/bin/phpunit                 # PHP: Core + Laravel suites (Laravel 10/11/12 in CI)

cd js && pnpm install
pnpm -r build && pnpm -r test      # JS: 4 packages (build before test)
pnpm -r typecheck

cd ../docs && pnpm docs:build      # docs site + vector drift guard
cd ../e2e  && pnpm exec playwright test   # browser round-trip (needs chromium)
```

## License

MIT.
