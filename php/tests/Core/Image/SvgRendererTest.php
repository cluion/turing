<?php
declare(strict_types=1);

namespace Cluion\Turing\Tests\Core\Image;

use Cluion\Turing\Core\Charset\DefaultCharset;
use Cluion\Turing\Core\Image\GlyphPaths;
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
     * Glyphs are stroked paths — never <text> — so F12 cannot copy the answer.
     */
    public function test_answer_is_not_inspectable_as_dom_text(): void
    {
        $secret = 'YEYG8';
        $svg = (new SvgRenderer())->render($secret);

        self::assertStringNotContainsString('<text', $svg);
        self::assertStringNotContainsString('</text>', $svg);
        // No character of the secret appears as SVG text content.
        foreach (str_split($secret) as $ch) {
            self::assertStringNotContainsString('>' . $ch . '<', $svg);
            self::assertStringNotContainsString('>' . $ch . '</', $svg);
        }
        self::assertStringNotContainsString($secret, $svg);
        // But each glyph still produced a path.
        self::assertSame(5, substr_count($svg, '<path '));
    }

    /**
     * Math expressions render operator/digit paths and keep a light background.
     */
    public function test_math_expression_and_background(): void
    {
        $svg = (new SvgRenderer())->render('3 + 4');
        self::assertStringNotContainsString('<text', $svg);
        self::assertStringNotContainsString('>3<', $svg);
        self::assertStringNotContainsString('>+<', $svg);
        self::assertStringContainsString('fill="#f4f4f5"', $svg);
        self::assertStringContainsString('<path ', $svg);
        self::assertStringContainsString('<line', $svg);
        // three non-space characters → three paths
        self::assertSame(3, substr_count($svg, '<path '));
    }

    /**
     * Every DefaultCharset letter has a path definition.
     */
    public function test_default_charset_alphabet_has_glyphs(): void
    {
        foreach ((new DefaultCharset())->alphabet() as $ch) {
            self::assertNotNull(
                GlyphPaths::pathFor($ch),
                "missing glyph for charset character {$ch}",
            );
        }
    }

    /**
     * Unknown characters become neutral blocks; raw code points are not emitted.
     */
    public function test_unknown_characters_do_not_leak_as_text(): void
    {
        $svg = (new SvgRenderer())->render('<&>');
        self::assertStringNotContainsString('<text', $svg);
        // Input was not HTML-escaped into the SVG (that would still leak intent).
        self::assertStringNotContainsString('&lt;', $svg);
        self::assertStringNotContainsString('&amp;', $svg);
        self::assertStringNotContainsString('&gt;', $svg);
        // Three unknown code points → three placeholder rects (plus the background rect).
        self::assertSame(4, substr_count($svg, '<rect'));
    }

    /**
     * Lowercase input uses the same uppercase path set (case-insensitive glyphs).
     */
    public function test_lowercase_maps_to_uppercase_paths(): void
    {
        $upper = (new SvgRenderer())->render('AB');
        $lower = (new SvgRenderer())->render('ab');
        self::assertSame(2, substr_count($upper, '<path '));
        self::assertSame(2, substr_count($lower, '<path '));
        self::assertStringNotContainsString('>a<', $lower);
        self::assertStringNotContainsString('>b<', $lower);
    }
}
