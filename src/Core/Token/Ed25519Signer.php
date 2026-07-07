<?php
declare(strict_types=1);

namespace Cluion\Turing\Core\Token;

use Cluion\Turing\Core\Exception\PowAlgorithmUnsupported;

/**
 * Asymmetric Ed25519 signer via ext-sodium. Opt-in: lets multiple servers
 * verify tokens without sharing the signing secret. Requires ext-sodium.
 */
final class Ed25519Signer implements Signer
{
    public function __construct(
        private readonly string $secretKey,
        private readonly string $publicKey,
    ) {
        if (!function_exists('sodium_crypto_sign')) {
            throw new PowAlgorithmUnsupported('ext-sodium is required for Ed25519.');
        }
    }

    public function sign(string $payload): string
    {
        // Detached: return only the 64-byte signature, matching the Signer
        // contract (raw signature bytes) and the token's two-segment layout.
        return sodium_crypto_sign_detached($payload, $this->secretKey);
    }

    public function verify(string $payload, string $signature): bool
    {
        // Returns false on any tamper or key mismatch.
        return sodium_crypto_sign_verify_detached($signature, $payload, $this->publicKey);
    }

    public function algorithm(): string
    {
        return 'EdDSA';
    }
}
