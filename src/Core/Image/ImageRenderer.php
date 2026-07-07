<?php
declare(strict_types=1);

namespace Cluion\Turing\Core\Image;

/**
 * Renders challenge text into embeddable image markup.
 */
interface ImageRenderer
{
    /**
     * Return image markup (an SVG string) for the given challenge text.
     */
    public function render(string $text, int $width = 120, int $height = 36): string;
}
