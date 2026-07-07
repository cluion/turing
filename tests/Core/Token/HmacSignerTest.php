<?php
declare(strict_types=1);

namespace Cluion\Turing\Tests\Core\Token;

use Cluion\Turing\Core\Token\HmacSigner;
use PHPUnit\Framework\TestCase;

final class HmacSignerTest extends TestCase
{
    /** A signature produced by sign() verifies against the same payload. */
    public function test_sign_then_verify_round_trips(): void
    {
        $signer = new HmacSigner('super-secret');
        $sig = $signer->sign('{"a":1}');
        self::assertTrue($signer->verify('{"a":1}', $sig));
    }

    /** A signature does not verify against a tampered payload. */
    public function test_verify_rejects_tampered_payload(): void
    {
        $signer = new HmacSigner('super-secret');
        $sig = $signer->sign('{"a":1}');
        self::assertFalse($signer->verify('{"a":2}', $sig));
    }

    /** Algorithm id is the JWS-style HS256. */
    public function test_algorithm_is_hs256(): void
    {
        self::assertSame('HS256', (new HmacSigner('k'))->algorithm());
    }
}
