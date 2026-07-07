<?php
declare(strict_types=1);

namespace Cluion\Turing\Tests\Core\Store;

use Cluion\Turing\Core\Store\NullStore;
use PHPUnit\Framework\TestCase;

final class NullStoreTest extends TestCase
{
    /**
     * The stateless store never tracks nonces, so consume always passes.
     */
    public function test_stateless_store_always_consumes(): void
    {
        $store = new NullStore();
        $store->remember('nonce-1', 60);
        self::assertTrue($store->consume('nonce-1'));   // never tracks - always pass
        self::assertTrue($store->consume('nonce-1'));   // still pass (no replay protection)
    }
}
