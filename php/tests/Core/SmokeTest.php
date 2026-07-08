<?php
declare(strict_types=1);

namespace Cluion\Turing\Tests\Core;

use Cluion\Turing\Core\Exception\TuringException;
use PHPUnit\Framework\TestCase;

final class SmokeTest extends TestCase
{
    /**
     * Verifies the autoloader and PHPUnit toolchain are wired up by asserting
     * the base exception interface loads.
     */
    public function test_toolchain_runs_and_exception_interface_exists(): void
    {
        self::assertTrue(interface_exists(TuringException::class));
    }
}
