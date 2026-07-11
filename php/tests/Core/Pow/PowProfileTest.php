<?php
declare(strict_types=1);

namespace Cluion\Turing\Tests\Core\Pow;

use Cluion\Turing\Core\Pow\PowProfile;
use PHPUnit\Framework\TestCase;

final class PowProfileTest extends TestCase
{
    public function test_balanced_is_default_band(): void
    {
        $cfg = PowProfile::apply([]);
        self::assertSame(PowProfile::BALANCED, $cfg['profile']);
        self::assertSame(5000, $cfg['cost']);
        self::assertSame(10000, $cfg['maxcounter']);
        self::assertSame('PBKDF2-SHA256', $cfg['algorithm']);
    }

    public function test_interactive_and_strict_bands(): void
    {
        $fast = PowProfile::apply(['profile' => 'interactive']);
        self::assertSame(1000, $fast['cost']);
        self::assertSame(2500, $fast['maxcounter']);

        $hard = PowProfile::apply(['profile' => 'strict']);
        self::assertSame(15000, $hard['cost']);
        self::assertSame(25000, $hard['maxcounter']);
    }

    public function test_explicit_cost_overrides_profile(): void
    {
        $cfg = PowProfile::apply(['profile' => 'strict', 'cost' => 42, 'maxcounter' => 99]);
        self::assertSame(42, $cfg['cost']);
        self::assertSame(99, $cfg['maxcounter']);
        self::assertSame(PowProfile::STRICT, $cfg['profile']);
    }

    public function test_unknown_profile_falls_back_to_balanced(): void
    {
        $cfg = PowProfile::apply(['profile' => 'nope']);
        self::assertSame(PowProfile::BALANCED, $cfg['profile']);
        self::assertSame(5000, $cfg['cost']);
    }

    public function test_shabit_profile_sets_difficulty_bits(): void
    {
        $cfg = PowProfile::apply(['algorithm' => 'SHA-256', 'profile' => 'interactive']);
        self::assertSame(16, $cfg['difficulty_bits']);
        self::assertArrayNotHasKey('cost', $cfg);
    }

    public function test_names_lists_three_bands(): void
    {
        self::assertSame(
            [PowProfile::INTERACTIVE, PowProfile::BALANCED, PowProfile::STRICT],
            PowProfile::names(),
        );
    }
}
