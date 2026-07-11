# Migrating from a classic Laravel captcha

If you already show an image (or math) challenge in Blade and validate it with
a framework rule, Turing maps to the same two-step habit: **one line to render,
one line to verify**. Prefer **PoW** for bot resistance; math/text stay
convenience-grade.

Turing does **not** ship a session-answer mode. Challenges are signed tokens;
on Laravel, the default cache store makes each nonce single-use.

## Side-by-side

| Classic pattern | Turing |
|-----------------|--------|
| Composer captcha package for Laravel only | `composer require cluion/turing` |
| Publish package config | `php artisan vendor:publish --tag=turing-config` + `TURING_SECRET` |
| Helper that prints an image / URL | `<x-turing type="pow" />` (or `math` / `text`) |
| Separate text input for the answer | Widget injects hidden `turing_token` (math/text also show a visible field) |
| Validation rule on the answer field | `'turing_token' => 'required\|turing'` |
| Answer stored in session | Signed token; `store=cache` for one-shot nonces |
| Different rules for “page” vs “API” modes | One field and one rule for every type |
| Server-side image generation (GD, etc.) | SVG paths (math/text) or no image (pow) |
| Laravel-only SDK | PHP Core + JS clients; frozen wire contract for other languages |

## Minimal Laravel swap

### Before

```blade
<form method="post" action="/register">
  @csrf
  {{-- image or captcha helper here --}}
  <input name="captcha" required>
  <button type="submit">Register</button>
</form>
```

```php
$request->validate([
    'captcha' => 'required|/* your old captcha rule */',
]);
```

### After (recommended: PoW)

Load the client widget once (Vite entry, layout, or CDN — see
[Plain HTML](/guide/plain-html)):

```js
import '@cluion/turing-core';
```

```blade
<form method="post" action="/register">
  @csrf
  <x-turing type="pow" />
  <button type="submit">Register</button>
</form>
```

```php
$request->validate([
    'turing_token' => 'required|turing',
]);
```

PoW shows a checkbox by default (user checks → browser solves → token injected).
For silent solve-on-load:

```blade
<x-turing type="pow" autostart />
```

### After (image-like math/text)

Closer to “type what you see”:

```blade
<x-turing type="math" />
{{-- or type="text" --}}
```

Still one packed `turing_token`. Treat strength as convenience-grade — use
**pow** when abuse resistance matters.

## Config checklist

```dotenv
TURING_SECRET=a-long-random-string
# optional difficulty: interactive | balanced | strict
# TURING_POW_PROFILE=balanced
```

```php
// config/turing.php (after publish)
'default' => 'pow',
'types' => [
    'pow' => [
        'algorithm' => 'PBKDF2-SHA256',
        'profile' => env('TURING_POW_PROFILE', 'balanced'),
        'expire' => 120,
    ],
],
'store' => 'cache', // single-use nonces; 'null' for pure stateless
```

## What you can drop

- Captcha-only image extensions on the default path  
- Session keys dedicated to captcha answers  
- Separate field names and rules per “mode”  
- Calling an external captcha SaaS for basic forms  

## Honest differences

| Topic | Notes |
|-------|--------|
| **Session answers** | Not shipped. Cache-backed single-use nonces cover most multi-server apps. |
| **Look & feel** | PoW is checkbox + status, not a warped PNG. Labels are customizable. |
| **Security** | PoW raises attack cost; math/text alone are weak. |
| **JavaScript** | A widget (or your own wire-contract client) is required. |

## Other front ends

- [Vue](/guide/vue) · [React](/guide/react) · [Plain HTML](/guide/plain-html)

Same `turing_token` and server rule regardless of UI.

## Next reading

- [Laravel guide](/guide/laravel) — install, profiles, component props  
- [Wire contract](/reference/wire-contract) — token bytes for custom clients  
- [Attributes](/reference/attributes) — `autostart`, labels, worker flags  
