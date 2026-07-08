<?php
declare(strict_types=1);

namespace Cluion\Turing\Tests\Laravel;

use Cluion\Turing\Laravel\TuringServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

/**
 * Base test case for the Laravel layer. Boots a minimal Laravel app via
 * testbench with the Turing service provider and a working secret registered.
 */
abstract class TestCase extends Orchestra
{
    /**
     * Register the package's service provider with the test app.
     *
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [TuringServiceProvider::class];
    }

    /**
     * Provide a deterministic secret and array cache for every test.
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('turing.secret', 'testbench-secret');
        $app['config']->set('cache.default', 'array');
    }
}
