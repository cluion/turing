<?php
declare(strict_types=1);

namespace Cluion\Turing\Core\Charset;

/**
 * Digits + uppercase Latin, ambiguous characters excluded (no 0/O/Q/1/I).
 * Verification elsewhere is case-insensitive, but the alphabet is uppercase only.
 */
final class DefaultCharset implements Charset
{
    private const AMBIGUOUS = ['0', 'O', 'Q', '1', 'I'];

    /** @var list<string> */
    private readonly array $alphabet;

    /**
     * Build the alphabet from 2-9 and A-Z, dropping ambiguous characters.
     */
    public function __construct()
    {
        $this->alphabet = array_values(array_filter(
            array_merge(range('2', '9'), range('A', 'Z')),
            fn(string $c) => !in_array($c, self::AMBIGUOUS, true)
        ));
    }

    /**
     * Return the allowed characters.
     *
     * @return list<string>
     */
    public function alphabet(): array
    {
        return $this->alphabet;
    }

    /**
     * Draw $length characters uniformly at random from the alphabet.
     */
    public function generate(int $length): string
    {
        $out = '';
        $max = count($this->alphabet) - 1;
        for ($i = 0; $i < $length; $i++) {
            // random_int is CSPRNG-backed, suitable for challenge secrets.
            $out .= $this->alphabet[random_int(0, $max)];
        }
        return $out;
    }
}
