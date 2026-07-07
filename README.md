# Turing

Self-hosted, zero-dependency, cross-language modern captcha. cluion brand.

## Core (this package)

Framework-agnostic PHP core. Token = `base64url(payload).base64url(signature)`
with canonical (sorted-key) JSON. Default signing: HMAC-SHA256 (Ed25519 opt-in
via ext-sodium). Challenge types: `math`, `text`, `pow` (PBKDF2-SHA256 default,
SHA-256 leading-zero-bit opt-in). Stateless by default; single-use via a `Store`.

```php
use Cluion\Turing\Core\Turing;
// The Core API is explicit by design; a framework integration layer provides
// the one-line sugar. See tests/Core/TuringFacadeTest.php for the full setup.
```

## Cross-language vectors

`tests/vectors/` is the wire contract. JS and other language ports MUST
reproduce these fixtures exactly (token bytes, PoW counters, answer hashes).
`keySignature` and any binary payload field are always base64url of the raw bytes.

## Development

```bash
composer install
vendor/bin/phpunit
```

Coverage needs a driver (pcov or xdebug) and runs in CI:

```bash
XDEBUG_MODE=coverage vendor/bin/phpunit --coverage-text
```

License: MIT.
