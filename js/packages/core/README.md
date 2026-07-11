# @cluion/turing-core

Headless client core for the Turing captcha: fetches a challenge, solves the
proof-of-work with native Web Crypto (no WASM), and injects the packed
`turing_token` for the enclosing form to submit. Zero runtime dependencies.

## Install (bundler)

```bash
pnpm add @cluion/turing-core
```

```js
import '@cluion/turing-core'; // auto-mounts every [data-turing] container
```

## CDN (plain HTML)

Pin an exact version and add Subresource Integrity so a CDN compromise cannot
swap the script (the `sha384` hash is generated after the first publish — see
[Publishing](#publishing)):

```html
<script
  src="https://cdn.jsdelivr.net/npm/@cluion/turing-core@0.2.0/dist/turing.global.js"
  integrity="sha384-mnsCwwvqQfqd7zQDpBAmT0Gj0sUWwYxYjmONH7/kiDmAZEvlg03ujfhxPqCUEFRV"
  crossorigin="anonymous"
  defer></script>

<form method="post" action="/submit">
  <div data-turing data-turing-url="/turing/challenge" data-turing-type="pow"></div>
  <button type="submit">Send</button>
</form>
```

The widget mounts onto `[data-turing]`, reads `data-turing-url` (and optional
`data-turing-type` / `data-turing-field`), solves the challenge, and adds a
hidden `turing_token` input. `window.Turing.mount(el)` is available for manual
control.

## Publishing

```bash
# one-time: npm login (an org member of @cluion)
cd js/packages/core
pnpm publish --access public          # runs prepublishOnly: typecheck + test + build

# verify
npm view @cluion/turing-core version
curl -sI https://unpkg.com/@cluion/turing-core/dist/turing.global.js | head -1

# generate the SRI hash for the docs (paste into the <script integrity=...>)
curl -s https://cdn.jsdelivr.net/npm/@cluion/turing-core@0.2.0/dist/turing.global.js \
  | openssl dgst -sha384 -binary | openssl base64 -A | sed 's/^/sha384-/'
```
