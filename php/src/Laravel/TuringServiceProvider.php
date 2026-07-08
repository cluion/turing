<?php
declare(strict_types=1);

namespace Cluion\Turing\Laravel;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Wires the Turing package into a Laravel app. Merges config and binds the
 * manager; the route, validator, and Blade component are added in a later task.
 */
final class TuringServiceProvider extends ServiceProvider
{
    /**
     * Merge the package's default config and bind the manager singleton.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/turing.php', 'turing');

        $this->app->singleton(TuringManager::class, static function ($app): TuringManager {
            return new TuringManager(
                (array) $app['config']->get('turing'),
                $app->make(CacheRepository::class),
            );
        });
    }
}
