<?php
declare(strict_types=1);

namespace Cluion\Turing\Tests\Core\Challenge;

use Cluion\Turing\Core\Challenge\PowType;
use Cluion\Turing\Core\KeyRing;
use Cluion\Turing\Core\Pow\Pbkdf2Solver;
use Cluion\Turing\Core\Token\HmacSigner;
use Cluion\Turing\Core\Token\TokenEncoder;
use PHPUnit\Framework\TestCase;

final class PowTypeTest extends TestCase
{
    /**
     * Build a single-key ring for signing challenge tokens.
     */
    private function ring(): KeyRing
    {
        return (new KeyRing('k1'))->add('k1', new HmacSigner('secret'));
    }

    /**
     * A PBKDF2 Deterministic challenge exposes its params (no image) and a
     * keySignature the client needs to know when to stop.
     */
    public function test_issue_pbkdf2_deterministic_returns_params(): void
    {
        $type = new PowType();
        $ch = $type->issue(
            ['algorithm' => 'PBKDF2-SHA256', 'cost' => 5000, 'maxcounter' => 10000, 'expire' => 120],
            $this->ring(),
            now: 1000
        );
        self::assertSame('pow', $ch->type);
        self::assertNull($ch->image);
        self::assertSame('PBKDF2-SHA256', $ch->params['algorithm']);
        self::assertArrayHasKey('keySignature', $ch->params);
    }

    /**
     * A precomputed counter (base64url keySignature) verifies via the solver.
     */
    public function test_precomputed_counter_verifies(): void
    {
        // Deterministic: the server "chose" counter 7 and embedded its derived
        // key as keySignature. Tiny cost so the test is fast.
        // ENCODING CONTRACT: keySignature is base64url of the raw derived key.
        $salt = 's';
        $nonce = 'n';
        $cost = 50;
        $good = 7;
        $sig = TokenEncoder::base64UrlEncode(
            hash_pbkdf2('sha256', $nonce . $good, $salt, $cost, 0, true)
        );
        $solver = new Pbkdf2Solver();
        self::assertTrue($solver->verify(
            ['salt' => $salt, 'nonce' => $nonce, 'cost' => $cost, 'keySignature' => $sig],
            $good
        ));
    }
}
