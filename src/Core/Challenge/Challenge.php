<?php
declare(strict_types=1);

namespace Cluion\Turing\Core\Challenge;

/**
 * Immutable challenge envelope handed to the client. `image` is set for visual
 * types; `params` carries the client-facing challenge fields (e.g. PoW inputs)
 * and stays null when there is nothing extra for the client.
 */
final readonly class Challenge
{
    /**
     * Capture the signed token plus the client-facing presentation fields.
     */
    public function __construct(
        public string $token,
        public ?string $image,
        public ?array $params,
        public string $type,
        public int $expires,
    ) {
    }
}
