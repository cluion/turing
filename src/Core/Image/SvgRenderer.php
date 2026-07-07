<?php
declare(strict_types=1);

namespace Cluion\Turing\Core\Image;

/**
 * Zero-extension SVG renderer. Each character is drawn as a transformed shape
 * (rotated/skewed) so the answer is not present as text nodes. Random noise
 * polylines add OCR friction. The current per-char shape is a stylized skeleton
 * (a rectangle plus a dot); swapping in real glyph paths is a later refinement
 * and does not affect the CSP-safe, no-<text> guarantee.
 */
final class SvgRenderer implements ImageRenderer
{
    /**
     * Draw each character as a rotated skeleton plus noise lines, wrapped in
     * an <svg> element sized to the requested width and height.
     */
    public function render(string $text, int $width = 120, int $height = 36): string
    {
        $len = strlen($text);
        $charW = intdiv($width, max(1, $len));
        $paths = '';
        for ($i = 0; $i < $len; $i++) {
            $x = $i * $charW + (int) ($charW * 0.2);
            $rot = random_int(-18, 18);
            $dy = random_int(-2, 2);
            // Per-char rotation/skew defeats naive segmentation; the shape itself
            // carries no letter information (no <text> node to scrape).
            $paths .= sprintf(
                '<g transform="translate(%d,%d) rotate(%d)"><rect x="2" y="6" width="%d" height="%d" rx="3" fill="none" stroke="#222" stroke-width="2"/><circle cx="%d" cy="%d" r="3" fill="#222"/></g>',
                $x,
                $dy,
                $rot,
                $charW - 6,
                $height - 16,
                intdiv($charW, 2),
                10
            );
        }
        // A few random polylines as background noise.
        $noise = '';
        for ($n = 0; $n < 4; $n++) {
            $noise .= sprintf(
                '<line x1="%d" y1="%d" x2="%d" y2="%d" stroke="#888" stroke-width="1"/>',
                random_int(0, $width),
                random_int(0, $height),
                random_int(0, $width),
                random_int(0, $height)
            );
        }
        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="%d" height="%d" viewBox="0 0 %d %d" role="img" aria-label="captcha">%s%s</svg>',
            $width,
            $height,
            $width,
            $height,
            $paths,
            $noise
        );
    }
}
