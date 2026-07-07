<?php
declare(strict_types=1);

namespace Cluion\Turing\Tests\Core\Token;

use Cluion\Turing\Core\Token\Ed25519Signer;
use PHPUnit\Framework\TestCase;

final class Ed25519SignerTest extends TestCase
{
    /**
     * Ed25519 is opt-in; skip the whole case when ext-sodium is absent.
     */
    protected function setUp(): void
    {
        if (!function_exists('sodium_crypto_sign_keypair')) {
            self::markTestSkipped('ext-sodium not available; Ed25519 is opt-in.');
        }
    }

    /**
     * A detached signature verifies against its payload.
     */
    public function test_sign_then_verify_round_trips(): void
    {
        $kp = sodium_crypto_sign_keypair();
        $secret = sodium_crypto_sign_secretkey($kp);
        $public = sodium_crypto_sign_publickey($kp);
        $signer = new Ed25519Signer($secret, $public);
        $sig = $signer->sign('{"a":1}');
        self::assertTrue($signer->verify('{"a":1}', $sig));
    }

    /**
     * Algorithm id is the JWS-style EdDSA.
     */
    public function test_algorithm_is_eddsa(): void
    {
        $kp = sodium_crypto_sign_keypair();
        $signer = new Ed25519Signer(sodium_crypto_sign_secretkey($kp), sodium_crypto_sign_publickey($kp));
        self::assertSame('EdDSA', $signer->algorithm());
    }
}
