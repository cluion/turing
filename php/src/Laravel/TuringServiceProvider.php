<?php
declare(strict_types=1);

namespace Cluion\Turing\Laravel;

use Illuminate\Support\ServiceProvider;

/**
 * Wires the Turing package into a Laravel app. This first cut only merges the
 * package config so the harness can boot; the manager binding, route,
 * validator, and Blade component are added by the tasks that need them.
 */
final class TuringServiceProvider extends ServiceProvider
{
    /**
     * Merge the package's default config under the 'turing' key.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/turing.php', 'turing');
    }
}
