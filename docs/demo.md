---
head:
  - - script
    - { src: ./turing.global.js, defer: '' }
---

# Live demo

This page loads the real `turing.global.js` widget and points it at a
pre-generated static PoW challenge (`/mock-challenge.json`), so the whole solve
runs **client-side with no backend** — exactly what a real page does, minus the
server issuing the challenge.

<ClientOnly>
<form method="post" action="#" style="display:grid;gap:1rem;max-width:32rem">
  <div
    data-turing
    data-turing-url="./mock-challenge.json"
    data-turing-type="pow"></div>
  <button type="button">Submit (token is injected above)</button>
</form>
</ClientOnly>

Open your dev tools and watch the widget mount, solve the proof-of-work with
native Web Crypto, and inject a hidden `turing_token` input into the form. On a
real site that token is submitted and verified server-side (see the
[Laravel guide](/guide/laravel)).

::: tip Same widget, real crypto
There is no mock solver here — the page loads the exact published bundle and
solves the challenge for real. Only the challenge JSON is static.
:::

## Deploying the docs

`docs:build` produces a fully static site in `docs/.vitepress/dist`. Serve that
directory from any static host (Netlify, Pages, S3, nginx). No runtime is
required. A CI job can build and publish it on push; the widget bundle is synced
into `public/` from the core package build.
