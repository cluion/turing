<?php
declare(strict_types=1);

namespace Cluion\Turing\Core\Charset;

/**
 * Supplies the alphabet and random strings used by text challenges.
 */
interface Charset
{
    /**
     * Return the allowed characters as an array of single-char strings.
     *
     * @return list<string>
     */
    public function alphabet(): array;

    /**
     * Return a CSPRNG-generated string of exactly $length chars from the alphabet.
     */
    public function generate(int $length): string;
}
