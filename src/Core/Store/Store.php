<?php
declare(strict_types=1);

namespace Cluion\Turing\Core\Store;

/**
 * Nonce store for single-use / replay protection.
 */
interface Store
{
    /**
     * Mark a freshly issued challenge's nonce as live for ttlSeconds.
     */
    public function remember(string $nonce, int $ttlSeconds): void;

    /**
     * Atomically mark a nonce consumed; returns true if it was live (valid
     * first use), false if unknown or already consumed (replay).
     */
    public function consume(string $nonce): bool;
}
