<?php
declare(strict_types=1);

namespace Cluion\Turing\Laravel;

use Cluion\Turing\Laravel\Blade\TuringComponent;
use Cluion\Turing\Laravel\Http\ChallengeController;
use Cluion\Turing\Laravel\Validation\TuringValidator;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;

/**
 * Wires the Turing package into a Laravel app: merges config, binds the
 * manager, publishes config, and registers the challenge route, the turing
 * validation rule, and the <x-turing/> Blade component.
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

    /**
     * Publish config and register route, validator, and Blade component.
     */
    public function boot(): void
    {
        $this->publishes(
            [__DIR__ . '/../../config/turing.php' => $this->app->configPath('turing.php')],
            'turing-config',
        );

        $this->registerRoute();
        $this->registerValidator();
        Blade::component('turing', TuringComponent::class);
    }

    /**
     * Register the named challenge route unless it is disabled in config.
     */
    private function registerRoute(): void
    {
        $route = (array) $this->app['config']->get('turing.route', []);
        if (($route['enabled'] ?? true) !== true) {
            return;
        }
        Route::middleware((array) ($route['middleware'] ?? []))
            ->get((string) ($route['uri'] ?? '/turing/challenge'), [ChallengeController::class, 'show'])
            ->name('turing.challenge');
    }

    /**
     * Register the 'turing' rule, delegating to TuringValidator.
     */
    private function registerValidator(): void
    {
        Validator::extend('turing', function ($attribute, $value, $parameters, $validator): bool {
            return $this->app->make(TuringValidator::class)->validate((string) $value);
        }, 'The :attribute failed the captcha check.');
    }
}
