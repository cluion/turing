<?php
declare(strict_types=1);

namespace Cluion\Turing\Tests\Core\Token;

use Cluion\Turing\Core\Token\TokenEncoder;
use PHPUnit\Framework\TestCase;

final class TokenEncoderTest extends TestCase
{
    /**
     * Encoding then decoding random bytes returns the original bytes.
     */
    public function test_base64url_round_trip(): void
    {
        $bin = random_bytes(64);
        self::assertSame($bin, TokenEncoder::base64UrlDecode(TokenEncoder::base64UrlEncode($bin)));
    }

    /**
     * base64url output must not contain +, / or = (URL-safe, unpadded).
     */
    public function test_base64url_has_no_padding_or_plus_slash(): void
    {
        $out = TokenEncoder::base64UrlEncode("\xff\xfe\xfb");
        self::assertDoesNotMatchRegularExpression('#[+/=]#', $out);
    }

    /**
     * Keys are sorted at every depth and the byte output is stable.
     */
    public function test_canonical_json_sorts_keys_and_is_stable(): void
    {
        $a = ['b' => 1, 'a' => ['y' => 2, 'x' => 1], 'c' => 3];
        $expected = '{"a":{"x":1,"y":2},"b":1,"c":3}';
        self::assertSame($expected, TokenEncoder::canonicalJson($a));
        // Re-encoding the decoded form yields identical bytes (stability).
        self::assertSame($expected, TokenEncoder::canonicalJson(json_decode($expected, true)));
    }
}
