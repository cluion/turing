<?php
declare(strict_types=1);

namespace Cluion\Turing\Tests\Laravel;

use Cluion\Turing\Laravel\CacheStore;
use Illuminate\Contracts\Cache\Repository;

final class CacheStoreTest extends TestCase
{
    /**
     * A remembered nonce consumes once, then never again (single-use).
     */
    public function test_remember_then_consume_is_single_use(): void
    {
        $store = new CacheStore($this->app->make(Repository::class));
        $store->remember('nonce-1', 60);
        self::assertTrue($store->consume('nonce-1'));   // first use
        self::assertFalse($store->consume('nonce-1'));  // replay rejected
    }

    /**
     * An unknown nonce never consumes.
     */
    public function test_unknown_nonce_does_not_consume(): void
    {
        $store = new CacheStore($this->app->make(Repository::class));
        self::assertFalse($store->consume('never-remembered'));
    }
}
