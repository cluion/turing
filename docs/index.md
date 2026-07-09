---
layout: home
hero:
  name: Turing
  text: A modern, self-hosted captcha
  tagline: Zero third-party dependencies, cross-language wire contract, native Web Crypto proof-of-work. No external service, no tracking.
  actions:
    - theme: brand
      text: Get started
      link: /guide/laravel
    - theme: alt
      text: Wire contract
      link: /reference/wire-contract
    - theme: alt
      text: Live demo
      link: /demo
features:
  - title: Self-hosted
    details: The server issues and verifies its own signed challenges. No calls to a third party, nothing to leak, no per-request quota.
  - title: Cross-language
    details: A frozen wire contract (token, canonical JSON, PoW) with byte-exact vectors every language port reproduces. PHP core ships first.
  - title: Proof-of-work
    details: Deterministic PBKDF2 or hashcash SHA-bit, solved in the browser with native Web Crypto — no WASM, CSP-safe, optionally offloaded to a Web Worker.
  - title: Drop-in clients
    details: One tag for plain HTML, a &lt;turing-captcha&gt; Web Component, and thin Vue and React wrappers, all over one headless core.
---

## What it is

Turing is cluion's self-hosted captcha: a signed challenge the server issues, a
client widget that solves it in the browser, and a token the enclosing form
submits for the server to verify. The whole thing is framework-agnostic — a PHP
**Core** defines the wire contract, framework **integrations** (Laravel first)
wire it into requests, and a **JS client** mounts the widget.

Pick your stack to get started:

- [Laravel](/guide/laravel) — `<x-turing>` Blade component + a validation rule
- [Plain HTML](/guide/plain-html) — one `<script>` and a `[data-turing]` container
- [Vue](/guide/vue) — `<Turing>` component
- [React](/guide/react) — `<Turing />` component

Porting to another language? The [wire contract](/reference/wire-contract) is the
authoritative spec, tied to the frozen test vectors.
