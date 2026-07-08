<?php
declare(strict_types=1);

namespace Cluion\Turing\Tests\Laravel;

use Cluion\Turing\Laravel\TuringManager;
use Illuminate\Support\Facades\Route;

final class ServiceProviderTest extends TestCase
{
    /**
     * The manager is bound as a singleton.
     */
    public function test_manager_is_bound_singleton(): void
    {
        self::assertInstanceOf(TuringManager::class, $this->app->make(TuringManager::class));
        self::assertSame($this->app->make(TuringManager::class), $this->app->make(TuringManager::class));
    }

    /**
     * The challenge route is registered under the name turing.challenge.
     */
    public function test_challenge_route_is_named(): void
    {
        self::assertTrue(Route::has('turing.challenge'));
    }
}
