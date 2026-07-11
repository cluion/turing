<?php
declare(strict_types=1);

namespace Cluion\Turing\Core\Image;

/**
 * Zero-extension SVG renderer. Each character is drawn as a stroked path glyph
 * (never a <text> node), with per-glyph rotation/jitter and noise lines.
 * Path strokes keep the answer out of DOM text content while remaining
 * human-readable at typical captcha sizes.
 */
final class SvgRenderer implements ImageRenderer
{
    /**
     * Draw each character as a distorted path glyph plus noise, wrapped in an
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
        // Scale unit cell (10×14) into the per-character slot with padding.
        $scale = min(($charW - 4) / GlyphPaths::CELL_W, ($height - 8) / GlyphPaths::CELL_H);

        $glyphs = '';
        $i = 0;
        foreach ($chars as $ch) {
            $slotX = 4 + $i * $charW;
            $i++;
            if (GlyphPaths::isSpacer($ch)) {
                continue;
            }

            $d = GlyphPaths::pathFor($ch);
            $rot = random_int(-16, 16);
            $dx = random_int(-1, 1);
            $dy = random_int(-2, 2);
            $cx = $slotX + $charW / 2 + $dx;
            $cy = $height / 2 + $dy;
            // Centre the unit cell on (cx, cy), then rotate around that point.
            $tx = $cx - (GlyphPaths::CELL_W * $scale) / 2;
            $ty = $cy - (GlyphPaths::CELL_H * $scale) / 2;

            if ($d !== null) {
                $glyphs .= sprintf(
                    '<g transform="rotate(%d %.2f %.2f) translate(%.2f %.2f) scale(%.3f)">'
                    . '<path d="%s" fill="none" stroke="#1a1a1a" stroke-width="1.6" '
                    . 'stroke-linecap="round" stroke-linejoin="round"/>'
                    . '</g>',
                    $rot,
                    $cx,
                    $cy,
                    $tx,
                    $ty,
                    $scale,
                    $d,
                );
            } else {
                // Unknown character: neutral block, never emit the raw code point.
                $glyphs .= sprintf(
                    '<rect x="%.1f" y="%.1f" width="%.1f" height="%.1f" rx="2" fill="none" stroke="#1a1a1a" stroke-width="1.5"/>',
                    $slotX + 3,
                    8 + $dy,
                    $charW - 8,
                    $height - 16,
                );
            }
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

        // Opaque light background so dark strokes stay readable on dark pages.
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
}
