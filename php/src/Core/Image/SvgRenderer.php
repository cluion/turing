<?php
declare(strict_types=1);

namespace Cluion\Turing\Core\Image;

/**
 * Zero-extension SVG renderer. Each character is drawn as a rotated/jittered
 * <text> glyph (readable by humans) plus noise lines for light OCR friction.
 * Glyph outlines as pure shapes remain a later refinement; v1 prioritises a
 * captcha a person can actually solve over anti-scrape purity.
 */
final class SvgRenderer implements ImageRenderer
{
    /**
     * Draw each character as distorted SVG text plus noise, wrapped in an
     * <svg> element. Width expands when the string is longer than the default
     * canvas can hold (e.g. math expressions like "9 + 8").
     */
    public function render(string $text, int $width = 120, int $height = 36): string
    {
        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);
        if ($chars === false) {
            $chars = str_split($text);
        }
        $len = max(1, count($chars));
        $charW = 22;
        $canvasW = max($width, $len * $charW + 8);
        $fontSize = max(16, $height - 12);

        $glyphs = '';
        $i = 0;
        foreach ($chars as $ch) {
            $x = 6 + $i * $charW;
            $i++;
            // Spaces advance the cursor but draw nothing.
            if ($ch === ' ') {
                continue;
            }
            $rot = random_int(-18, 18);
            $dy = random_int(-3, 3);
            $y = (int) ($height * 0.72) + $dy;
            $glyphs .= sprintf(
                '<text x="%d" y="%d" transform="rotate(%d %d %d)" font-family="ui-monospace, SFMono-Regular, Menlo, Consolas, monospace" font-size="%d" font-weight="700" fill="#1a1a1a">%s</text>',
                $x,
                $y,
                $rot,
                $x,
                $y,
                $fontSize,
                $this->escape($ch),
            );
        }

        $noise = '';
        for ($n = 0; $n < 5; $n++) {
            $noise .= sprintf(
                '<line x1="%d" y1="%d" x2="%d" y2="%d" stroke="#999" stroke-width="1" opacity="0.7"/>',
                random_int(0, $canvasW),
                random_int(0, $height),
                random_int(0, $canvasW),
                random_int(0, $height),
            );
        }

        // Opaque light background so dark glyphs stay readable on dark pages.
        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="%d" height="%d" viewBox="0 0 %d %d" role="img" aria-label="captcha"><rect width="100%%" height="100%%" fill="#f4f4f5"/>%s%s</svg>',
            $canvasW,
            $height,
            $canvasW,
            $height,
            $glyphs,
            $noise,
        );
    }

    /**
     * Escape a single character for safe inclusion in SVG/XML text content.
     */
    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
