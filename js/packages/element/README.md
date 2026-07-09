# @cluion/turing-element

A `<turing-captcha>` custom element wrapping [`@cluion/turing-core`](../core).
Drop one tag inside a `<form>` and it renders a light-DOM container, solves the
proof-of-work, and injects the hidden `turing_token` for the form to submit. It
is the shared base for the Vue and React wrappers. Zero runtime dependencies
beyond the core.

## Install (bundler)

```bash
pnpm add @cluion/turing-element
```

```js
import '@cluion/turing-element'; // registers <turing-captcha> once
```

## CDN (plain HTML)

Pin an exact version and add Subresource Integrity so a CDN compromise cannot
swap the script:

```html
<script
  src="https://cdn.jsdelivr.net/npm/@cluion/turing-element@0.1.0/dist/turing-element.global.js"
  integrity="sha384-REPLACE_WITH_PUBLISHED_HASH"
  crossorigin="anonymous"
  defer></script>

<form method="post" action="/submit">
  <turing-captcha url="/turing/challenge" type="pow"></turing-captcha>
  <button type="submit">Send</button>
</form>
```

The element uses **light DOM** (no shadow root) on purpose: the injected hidden
`turing_token` input must live inside the surrounding `<form>` to be submitted.

## Attributes

| Attribute | Required | Maps to (core) | Description |
|-----------|----------|----------------|-------------|
| `url`     | yes      | `data-turing-url`   | Challenge endpoint URL. |
| `type`    | no       | `data-turing-type`  | Challenge type, e.g. `pow`. |
| `field`   | no       | `data-turing-field` | Hidden input name (default `turing_token`). |

Progress is reflected on `data-turing-state` (`solved` / `error`).

## Events

Both bubble, so a listener on the `<form>` (or above) catches them:

| Event           | When | `detail` |
|-----------------|------|----------|
| `turing:solved` | The token was injected. | — |
| `turing:error`  | Fetch/solve/validation failed. | `{ error }` |

```js
document.querySelector('turing-captcha')
  .addEventListener('turing:solved', () => console.log('ready to submit'));
```
