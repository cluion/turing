<?php
declare(strict_types=1);

namespace Cluion\Turing\Tests\Core\Image;

use Cluion\Turing\Core\Image\SvgRenderer;
use PHPUnit\Framework\TestCase;

final class SvgRendererTest extends TestCase
{
    /**
     * The output is an SVG element carrying at least the requested dimensions.
     */
    public function test_produces_svg_with_correct_dimensions(): void
    {
        $svg = (new SvgRenderer())->render('AB23', width: 120, height: 36);
        self::assertStringStartsWith('<svg', $svg);
        self::assertStringContainsString('height="36"', $svg);
        self::assertStringContainsString('</svg>', $svg);
        self::assertMatchesRegularExpression('/width="\d+"/', $svg);
    }

    /**
     * Humans must be able to read the challenge — each character is a <text> glyph.
     */
    public function test_answer_characters_are_rendered_as_text(): void
    {
        $svg = (new SvgRenderer())->render('AB23');
        self::assertStringContainsString('<text', $svg);
        self::assertStringContainsString('>A</text>', $svg);
        self::assertStringContainsString('>B</text>', $svg);
        self::assertStringContainsString('>2</text>', $svg);
        self::assertStringContainsString('>3</text>', $svg);
    }

    /**
     * Math expressions render operators and keep a light background for contrast.
     */
    public function test_math_expression_and_background(): void
    {
        $svg = (new SvgRenderer())->render('3 + 4');
        self::assertStringContainsString('>3</text>', $svg);
        self::assertStringContainsString('>+</text>', $svg);
        self::assertStringContainsString('>4</text>', $svg);
        self::assertStringContainsString('fill="#f4f4f5"', $svg);
        self::assertStringContainsString('<line', $svg);
    }

    /**
     * XML-special characters are escaped so the SVG stays well-formed.
     */
    public function test_escapes_xml_special_characters(): void
    {
        $svg = (new SvgRenderer())->render('<&>');
        self::assertStringNotContainsString('><&></text>', $svg);
        self::assertStringContainsString('&lt;', $svg);
        self::assertStringContainsString('&amp;', $svg);
        self::assertStringContainsString('&gt;', $svg);
    }
}
