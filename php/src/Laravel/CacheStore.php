<?php
declare(strict_types=1);

namespace Cluion\Turing\Laravel;

use Cluion\Turing\Core\Store\Store;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

/**
 * Single-use nonce store backed by the Laravel cache. remember() marks a nonce
 * live for a TTL; consume() uses pull() (get-and-forget) so a nonce can only be
 * consumed once. The pull is not strictly atomic, but the race window is far
 * narrower than the token's lifetime and acceptable for captcha; use a lock for
 * stricter guarantees.
 */
final class CacheStore implements Store
{
    private const PREFIX = 'turing:nonce:';

    /**
     * Hold the cache repository the store reads and writes.
     */
    public function __construct(private readonly CacheRepository $cache)
    {
    }

    /**
     * Mark a freshly issued challenge's nonce as live for ttlSeconds.
     */
    public function remember(string $nonce, int $ttlSeconds): void
    {
        $this->cache->put(self::PREFIX . $nonce, true, $ttlSeconds);
    }

    /**
     * Consume a nonce: true only if it was live and not yet consumed.
     */
    public function consume(string $nonce): bool
    {
        // pull() returns the value and forgets it in one call, so a second
        // consume of the same nonce returns null (already forgotten).
        return $this->cache->pull(self::PREFIX . $nonce) !== null;
    }
}
