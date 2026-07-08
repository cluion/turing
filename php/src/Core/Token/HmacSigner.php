<?php
declare(strict_types=1);

namespace Cluion\Turing\Core\Token;

/**
 * Symmetric HMAC-SHA256 signer (HS256). The default: one shared secret,
 * native in every target language, zero extensions required.
 */
final class HmacSigner implements Signer
{
    /**
     * Hold the shared secret used for both signing and verifying.
     */
    public function __construct(private readonly string $secret)
    {
    }

    /**
     * Produce the raw HMAC-SHA256 signature over the payload.
     */
    public function sign(string $payload): string
    {
        return hash_hmac('sha256', $payload, $this->secret, true);
    }

    /**
     * Recompute the signature and compare in constant time.
     */
    public function verify(string $payload, string $signature): bool
    {
        return hash_equals($this->sign($payload), $signature);
    }

    /**
     * Return the JWS-style algorithm id.
     */
    public function algorithm(): string
    {
        return 'HS256';
    }
}
