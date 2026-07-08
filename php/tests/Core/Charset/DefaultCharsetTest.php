<?php
declare(strict_types=1);

namespace Cluion\Turing\Tests\Core\Charset;

use Cluion\Turing\Core\Charset\DefaultCharset;
use PHPUnit\Framework\TestCase;

final class DefaultCharsetTest extends TestCase
{
    /**
     * The generated string has exactly the requested length.
     */
    public function test_length_is_respected(): void
    {
        self::assertSame(5, strlen((new DefaultCharset())->generate(5)));
    }

    /**
     * The alphabet excludes the visually ambiguous characters.
     */
    public function test_excludes_ambiguous_chars(): void
    {
        $charset = new DefaultCharset();
        $alphabet = $charset->alphabet();
        foreach (['0', 'O', 'Q', '1', 'I'] as $ambiguous) {
            self::assertNotContains($ambiguous, $alphabet);
        }
    }

    /**
     * Every generated character comes from the declared alphabet.
     */
    public function test_generated_chars_all_within_alphabet(): void
    {
        $charset = new DefaultCharset();
        $alphabet = $charset->alphabet();
        for ($i = 0; $i < 50; $i++) {
            $out = $charset->generate(8);
            for ($j = 0; $j < strlen($out); $j++) {
                self::assertContains($out[$j], $alphabet);
            }
        }
    }
}
