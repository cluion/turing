<?php
declare(strict_types=1);

namespace Cluion\Turing\Core\Token;

/**
 * Symmetric HMAC-SHA256 signer (HS256). The default: one shared secret,
 * native in every target language, zero extensions required.
 */
final class HmacSigner implements Signer
{
    public function __construct(private readonly string $secret)
    {
    }

    public function sign(string $payload): string
    {
        return hash_hmac('sha256', $payload, $this->secret, true);
    }

    public function verify(string $payload, string $signature): bool
    {
        // hash_equals guards against timing attacks on the comparison.
        return hash_equals($this->sign($payload), $signature);
    }

    public function algorithm(): string
    {
        return 'HS256';
    }
}
