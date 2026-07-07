<?php
declare(strict_types=1);

namespace Cluion\Turing\Core\Token;

/**
 * Signs and verifies the exact payload bytes of a token. Implementations
 * differ only in the underlying primitive (HMAC, Ed25519, ...).
 */
interface Signer
{
    /** Return raw signature bytes over the exact payload bytes. */
    public function sign(string $payload): string;

    /** Constant-time check; true iff the signature is valid for the payload. */
    public function verify(string $payload, string $signature): bool;

    /** Short algorithm id stored for reference (e.g. HS256, EdDSA). */
    public function algorithm(): string;
}
