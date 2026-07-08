<?php
declare(strict_types=1);

namespace Cluion\Turing\Tests\Core\Token;

use Cluion\Turing\Core\Exception\SignatureInvalid;
use Cluion\Turing\Core\Exception\TokenInvalid;
use Cluion\Turing\Core\Token\HmacSigner;
use Cluion\Turing\Core\Token\Payload;
use Cluion\Turing\Core\Token\Token;
use Cluion\Turing\Core\Token\TokenEncoder;
use PHPUnit\Framework\TestCase;

final class TokenTest extends TestCase
{
    /**
     * A fixed sample payload shared by the cases below.
     */
    private function payload(): Payload
    {
        return new Payload(type: 'math', kid: 'k1', nonce: 'n', iat: 1000, exp: 1120, data: ['ah' => 'x']);
    }

    /**
     * Signing then decoding recovers the original payload fields.
     */
    public function test_round_trip_preserves_payload(): void
    {
        $signer = new HmacSigner('s');
        $compact = (string) Token::sign($this->payload(), $signer);
        self::assertSame('k1', Token::decode($compact, $signer)->kid);
    }

    /**
     * A string without two segments is rejected as malformed.
     */
    public function test_decode_rejects_malformed_compact(): void
    {
        $this->expectException(TokenInvalid::class);
        Token::decode('not-a-token', new HmacSigner('s'));
    }

    /**
     * Flipping a signature byte is caught before the payload is trusted.
     */
    public function test_decode_rejects_bad_signature(): void
    {
        $signer = new HmacSigner('s');
        $compact = (string) Token::sign($this->payload(), $signer);
        // Flip the last char of the signature segment.
        $tampered = substr($compact, 0, -1) . (substr($compact, -1) === 'A' ? 'B' : 'A');
        $this->expectException(SignatureInvalid::class);
        Token::decode($tampered, $signer);
    }

    /**
     * Segments that are not valid base64url are rejected as malformed.
     */
    public function test_decode_rejects_non_base64url_segments(): void
    {
        $this->expectException(TokenInvalid::class);
        // '@' is not a base64url character, so strict decoding fails.
        Token::decode('@@@.@@@', new HmacSigner('s'));
    }

    /**
     * A validly signed but non-JSON payload is rejected as malformed.
     */
    public function test_decode_rejects_non_json_payload(): void
    {
        $signer = new HmacSigner('s');
        $json = 'not-json';
        // Sign the raw bytes so the signature passes but json_decode fails.
        $compact = TokenEncoder::base64UrlEncode($json)
            . '.'
            . TokenEncoder::base64UrlEncode($signer->sign($json));
        $this->expectException(TokenInvalid::class);
        Token::decode($compact, $signer);
    }
}
