# Turing

Self-hosted, zero-dependency, cross-language modern captcha. cluion brand.

## Core

Framework-agnostic PHP core under `php/src/Core`. Token = `base64url(payload).base64url(signature)`
with canonical (sorted-key) JSON. HMAC-SHA256 default signing (Ed25519 opt-in).
Challenge types: `math`, `text`, `pow` (PBKDF2-SHA256 default, SHA-256 bit opt-in).
Stateless by default; single-use via a `Store`.

## Laravel

One line to show, one line to verify:

```blade
<x-turing type="pow" />
```

```php
$request->validate(['turing_token' => 'required|turing']);
// or: Turing::verifyRequest($request);
```

Publish config with `php artisan vendor:publish --tag=turing-config`, then set
`TURING_SECRET`. The `<x-turing/>` component renders a CSP-safe container the
client widget mounts onto; the widget ships separately.

## JS client (headless core)

Bundler apps install from npm:

```bash
pnpm add @cluion/turing-core
```

```js
import '@cluion/turing-core'; // auto-mounts every [data-turing] container
```

Plain HTML pages load the browser global from a CDN. Pin an exact version and
add Subresource Integrity so a CDN compromise cannot swap the script:

```html
<script
  src="https://cdn.jsdelivr.net/npm/@cluion/turing-core@0.1.0/dist/turing.global.js"
  integrity="sha384-REPLACE_WITH_PUBLISHED_HASH"
  crossorigin="anonymous"
  defer></script>

<form method="post" action="/submit">
  <div data-turing data-turing-url="/turing/challenge" data-turing-type="pow"></div>
  <button type="submit">Send</button>
</form>
```

The core fetches the challenge from `data-turing-url`, solves PoW with native
Web Crypto (no WASM), and injects the packed `turing_token` for the form to
submit. A runnable page is in [`examples/plain-html/index.html`](examples/plain-html/index.html);
see [`js/packages/core/README.md`](js/packages/core/README.md) for details.
Framework adapters (Web Component, Vue, React) ship separately.

## Cross-language vectors

`php/tests/vectors/` is the wire contract. Language ports MUST reproduce these
fixtures exactly (token bytes, PoW counters, answer hashes). Binary payload
fields (e.g. `keySignature`) are always base64url of the raw bytes.

## Development

```bash
composer install
vendor/bin/phpunit                 # Core + Laravel suites
vendor/bin/phpunit --testsuite Core
```

Coverage needs a driver (pcov or xdebug) and runs in CI:

```bash
XDEBUG_MODE=coverage vendor/bin/phpunit --coverage-text
```

License: MIT.
