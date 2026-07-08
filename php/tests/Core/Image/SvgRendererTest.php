<?php
declare(strict_types=1);

namespace Cluion\Turing\Tests\Core\Image;

use Cluion\Turing\Core\Image\SvgRenderer;
use PHPUnit\Framework\TestCase;

final class SvgRendererTest extends TestCase
{
    /**
     * The output is an SVG element carrying the requested dimensions.
     */
    public function test_produces_svg_with_correct_dimensions(): void
    {
        $svg = (new SvgRenderer())->render('AB23', width: 120, height: 36);
        self::assertStringStartsWith('<svg', $svg);
        self::assertStringContainsString('width="120"', $svg);
        self::assertStringContainsString('height="36"', $svg);
        self::assertStringContainsString('</svg>', $svg);
    }

    /**
     * The answer text is never emitted as inspectable text nodes.
     */
    public function test_answer_text_is_not_inspectable_as_text(): void
    {
        $svg = (new SvgRenderer())->render('SECRET');
        // Characters are drawn as shapes, never as <text> with the answer.
        self::assertStringNotContainsString('>S<', $svg);
        self::assertStringNotContainsString('<text', $svg);
    }
}
