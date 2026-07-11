<?php
declare(strict_types=1);

namespace Cluion\Turing\Tests\Laravel;

/**
 * Confirms the testbench harness boots the app with our provider registered
 * and the package config merged.
 */
final class SmokeTest extends TestCase
{
    /**
     * The turing config is merged and available.
     */
    public function test_app_boots_with_turing_config(): void
    {
        self::assertIsArray($this->app['config']->get('turing'));
        self::assertSame('pow', $this->app['config']->get('turing.default'));
    }
}
