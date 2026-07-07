<?php
declare(strict_types=1);

namespace Cluion\Turing\Core\Challenge;

/**
 * Immutable challenge envelope. `image` is set for visual types; `params`
 * carries client-facing challenge fields (e.g. PoW inputs) and stays null when
 * there is nothing extra. `nonce` is the single-use id (also inside the token)
 * exposed so the facade can register it without re-decoding.
 */
final readonly class Challenge
{
    /**
     * Capture the signed token, presentation fields, and single-use nonce.
     */
    public function __construct(
        public string $token,
        public ?string $image,
        public ?array $params,
        public string $type,
        public int $expires,
        public string $nonce,
    ) {
    }
}
