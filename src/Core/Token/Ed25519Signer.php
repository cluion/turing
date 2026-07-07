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
    /**
     * Hold the keypair and fail fast if ext-sodium is unavailable.
     */
    public function __construct(
        private readonly string $secretKey,
        private readonly string $publicKey,
    ) {
        if (!function_exists('sodium_crypto_sign')) {
            throw new PowAlgorithmUnsupported('ext-sodium is required for Ed25519.');
        }
    }

    /**
     * Produce a detached 64-byte signature, matching the Signer contract
     * (raw signature bytes) and the token's two-segment layout. Using
     * sodium_crypto_sign() would prepend the message and double the payload
     * at verify time.
     */
    public function sign(string $payload): string
    {
        return sodium_crypto_sign_detached($payload, $this->secretKey);
    }

    /**
     * Verify a detached signature; false on any tamper or key mismatch.
     */
    public function verify(string $payload, string $signature): bool
    {
        return sodium_crypto_sign_verify_detached($signature, $payload, $this->publicKey);
    }

    /**
     * Return the JWS-style algorithm id.
     */
    public function algorithm(): string
    {
        return 'EdDSA';
    }
}
