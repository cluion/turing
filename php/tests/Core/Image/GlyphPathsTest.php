<?php
declare(strict_types=1);

namespace Cluion\Turing\Tests\Core\Image;

use Cluion\Turing\Core\Image\GlyphPaths;
use PHPUnit\Framework\TestCase;

final class GlyphPathsTest extends TestCase
{
    /**
     * Digits and operators used by math challenges are defined.
     */
    public function test_math_characters_are_defined(): void
    {
        foreach (['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '+', '-'] as $ch) {
            self::assertNotNull(GlyphPaths::pathFor($ch), "missing {$ch}");
            self::assertStringContainsString('M ', GlyphPaths::pathFor($ch) ?? '');
        }
    }

    public function test_spacer(): void
    {
        self::assertTrue(GlyphPaths::isSpacer(' '));
        self::assertFalse(GlyphPaths::isSpacer('A'));
    }

    public function test_unknown_returns_null(): void
    {
        self::assertNull(GlyphPaths::pathFor('€'));
        self::assertNull(GlyphPaths::pathFor(''));
    }
}
