# Plain HTML

No bundler required — load the browser global from a CDN and add a
`[data-turing]` container inside your form.

## Add the script and a container

Pin an exact version and add Subresource Integrity so a CDN compromise cannot
swap the script:

```html
<script
  src="https://cdn.jsdelivr.net/npm/@cluion/turing-core@0.1.0/dist/turing.global.js"
  integrity="sha384-CUAGKKDGZDcu6hnrOgnpoNw7eLOC9QTfaUAeLTI4OoN+Xnb2kgGacoBZQkELpCy0"
  crossorigin="anonymous"
  defer></script>

<form method="post" action="/submit">
  <div data-turing data-turing-url="/turing/challenge" data-turing-type="pow"></div>
  <button type="submit">Send</button>
</form>
```

On `DOMContentLoaded` the widget finds every `[data-turing]` container, reads
`data-turing-url` (plus optional `data-turing-type` / `data-turing-field`),
fetches and solves the challenge, and adds a hidden `turing_token` input to the
enclosing `<form>`. `window.Turing.mount(el)` is available for manual control.

Generate the `sha384` hash after publishing:

```bash
curl -s https://cdn.jsdelivr.net/npm/@cluion/turing-core@0.1.0/dist/turing.global.js \
  | openssl dgst -sha384 -binary | openssl base64 -A | sed 's/^/sha384-/'
```

## Bundler apps

If you use Vite/webpack, install from npm instead — importing the package
auto-mounts every `[data-turing]` container:

```bash
pnpm add @cluion/turing-core
```

```js
import '@cluion/turing-core';
```

See [Attributes & props](/reference/attributes) for the full `data-turing-*`
list.
