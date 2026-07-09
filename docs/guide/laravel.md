# Laravel

Turing ships a Laravel integration: a service provider, a `<x-turing>` Blade
component that renders the mount point, a challenge endpoint, and a `turing`
validation rule.

## Install

```bash
composer require cluion/turing
```

Publish the config and set a secret:

```bash
php artisan vendor:publish --tag=turing-config
```

```dotenv
# .env — required; tokens are signed with this.
TURING_SECRET=change-me-to-a-long-random-string
```

The service provider auto-registers a named challenge route (`turing.challenge`
at `/turing/challenge`), the `turing` validator, and the `<x-turing>` component.

## Render the widget

Drop the component inside a `<form>` and add the browser widget (see
[Plain HTML](/guide/plain-html) for the `<script>`):

```blade
<form method="post" action="/submit">
  @csrf
  <x-turing type="pow" />
  <button type="submit">Submit</button>
</form>
```

`<x-turing>` renders a CSP-safe container — a `data-turing` `<div>` with the
resolved challenge URL. No inline script is emitted. The client widget mounts
onto it, solves the challenge, and injects a hidden `turing_token` input.

## Verify the submission

Validate `turing_token` with the `turing` rule:

```php
Route::post('/submit', function (Illuminate\Http\Request $request) {
    $request->validate(['turing_token' => 'required|turing']);

    return response()->json(['ok' => true]);
});
```

The rule fails safely — a wrong answer or tampered token returns a validation
error, never an exception.

## Single-use nonces

By default the store is the cache (`Cache::pull`, get-and-forget), making each
nonce one-shot so a solved token cannot be replayed. Point `turing.store` at any
cache the app already uses.

A full runnable example lives in [`workbench/`](https://git.cluion.com/ningyungame/turing)
(`vendor/bin/testbench serve`, then visit `/captcha-demo`).
