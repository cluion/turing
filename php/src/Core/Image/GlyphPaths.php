<?php
declare(strict_types=1);

namespace Cluion\Turing\Core\Image;

/**
 * Stick-font path data for captcha glyphs. Each entry is an SVG path `d` in a
 * unit cell of 0..10 × 0..14. Rendered as stroked paths (never <text>), so the
 * answer cannot be scraped as DOM text content.
 *
 * Covers digits, A–Z, and math operators used by MathType / TextType.
 */
final class GlyphPaths
{
    /**
     * Unit cell width / height used when authoring path coordinates.
     */
    public const CELL_W = 10.0;
    public const CELL_H = 14.0;

    /**
     * Return the path `d` for a single character, or null if unknown.
     * Lookup is case-insensitive for letters (alphabet is uppercase-only).
     */
    public static function pathFor(string $char): ?string
    {
        if ($char === '') {
            return null;
        }
        if (isset(self::PATHS[$char])) {
            return self::PATHS[$char];
        }
        $upper = strtoupper($char);
        return self::PATHS[$upper] ?? null;
    }

    /**
     * Whether the character advances the cursor without drawing (whitespace).
     */
    public static function isSpacer(string $char): bool
    {
        return $char === ' ' || $char === "\t";
    }

    /**
     * @var array<string, string>
     */
    private const PATHS = [
        // Digits
        '0' => 'M 3,2 L 7,2 L 8,4 L 8,10 L 7,12 L 3,12 L 2,10 L 2,4 Z',
        '1' => 'M 4,3 L 5,2 L 5,12 M 3,12 L 7,12',
        '2' => 'M 2,4 L 2,3 L 4,2 L 7,2 L 8,3 L 8,5 L 2,11 L 2,12 L 8,12',
        '3' => 'M 2,3 L 4,2 L 7,2 L 8,3 L 8,5 L 6,7 L 8,9 L 8,11 L 7,12 L 4,12 L 2,11',
        '4' => 'M 6,2 L 2,8 L 8,8 M 6,2 L 6,12',
        '5' => 'M 8,2 L 3,2 L 2,7 L 6,6 L 8,7 L 8,10 L 7,12 L 3,12 L 2,11',
        '6' => 'M 7,3 L 5,2 L 3,3 L 2,6 L 2,10 L 3,12 L 6,12 L 8,10 L 8,8 L 6,7 L 3,7 L 2,8',
        '7' => 'M 2,2 L 8,2 L 4,12 M 4,7 L 7,7',
        '8' => 'M 5,2 L 7,3 L 7,6 L 5,7 L 3,6 L 3,3 Z M 5,7 L 7,8 L 7,11 L 5,12 L 3,11 L 3,8 Z',
        '9' => 'M 8,6 L 7,7 L 4,7 L 2,5 L 2,3 L 3,2 L 6,2 L 8,4 L 8,10 L 7,12 L 4,12 L 3,11',

        // Operators (math challenges)
        '+' => 'M 5,3 L 5,11 M 2,7 L 8,7',
        '-' => 'M 2,7 L 8,7',
        '=' => 'M 2,5 L 8,5 M 2,9 L 8,9',
        '×' => 'M 3,4 L 7,10 M 7,4 L 3,10',
        '*' => 'M 5,3 L 5,11 M 3,5 L 7,9 M 7,5 L 3,9',

        // Latin uppercase
        'A' => 'M 2,12 L 5,2 L 8,12 M 3.2,8 L 6.8,8',
        'B' => 'M 2,2 L 2,12 L 6,12 L 8,10 L 8,8 L 6,7 L 8,6 L 8,4 L 6,2 Z M 2,7 L 6,7',
        'C' => 'M 8,4 L 7,2 L 4,2 L 2,4 L 2,10 L 4,12 L 7,12 L 8,10',
        'D' => 'M 2,2 L 2,12 L 5,12 L 8,9 L 8,5 L 5,2 Z',
        'E' => 'M 8,2 L 2,2 L 2,12 L 8,12 M 2,7 L 6,7',
        'F' => 'M 2,12 L 2,2 L 8,2 M 2,7 L 6,7',
        'G' => 'M 8,4 L 7,2 L 4,2 L 2,4 L 2,10 L 4,12 L 7,12 L 8,10 L 8,7 L 5,7',
        'H' => 'M 2,2 L 2,12 M 8,2 L 8,12 M 2,7 L 8,7',
        'I' => 'M 3,2 L 7,2 M 5,2 L 5,12 M 3,12 L 7,12',
        'J' => 'M 3,2 L 8,2 L 8,10 L 6,12 L 4,12 L 2,10',
        'K' => 'M 2,2 L 2,12 M 8,2 L 2,7 L 8,12',
        'L' => 'M 2,2 L 2,12 L 8,12',
        'M' => 'M 2,12 L 2,2 L 5,8 L 8,2 L 8,12',
        'N' => 'M 2,12 L 2,2 L 8,12 L 8,2',
        'O' => 'M 3,2 L 7,2 L 8,4 L 8,10 L 7,12 L 3,12 L 2,10 L 2,4 Z',
        'P' => 'M 2,12 L 2,2 L 6,2 L 8,4 L 8,6 L 6,8 L 2,8',
        'Q' => 'M 3,2 L 7,2 L 8,4 L 8,10 L 7,12 L 3,12 L 2,10 L 2,4 Z M 6,9 L 8,12',
        'R' => 'M 2,12 L 2,2 L 6,2 L 8,4 L 8,6 L 6,8 L 2,8 M 5,8 L 8,12',
        'S' => 'M 8,3 L 7,2 L 3,2 L 2,3 L 2,5 L 3,6 L 7,7 L 8,8 L 8,11 L 7,12 L 3,12 L 2,11',
        'T' => 'M 2,2 L 8,2 M 5,2 L 5,12',
        'U' => 'M 2,2 L 2,10 L 4,12 L 6,12 L 8,10 L 8,2',
        'V' => 'M 2,2 L 5,12 L 8,2',
        'W' => 'M 1.5,2 L 3,12 L 5,6 L 7,12 L 8.5,2',
        'X' => 'M 2,2 L 8,12 M 8,2 L 2,12',
        'Y' => 'M 2,2 L 5,7 L 8,2 M 5,7 L 5,12',
        'Z' => 'M 2,2 L 8,2 L 2,12 L 8,12',
    ];
}
