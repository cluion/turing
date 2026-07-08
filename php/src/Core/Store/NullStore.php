<?php
declare(strict_types=1);

namespace Cluion\Turing\Core\Store;

/**
 * Stateless no-op store (Core default). No replay protection - security rests
 * on the token's short expiry. Framework layers swap in a real cache-backed store.
 */
final class NullStore implements Store
{
    /**
     * No-op: nothing is tracked.
     */
    public function remember(string $nonce, int $ttlSeconds): void
    {
    }

    /**
     * Always succeeds since no nonce is ever tracked.
     */
    public function consume(string $nonce): bool
    {
        return true;
    }
}
