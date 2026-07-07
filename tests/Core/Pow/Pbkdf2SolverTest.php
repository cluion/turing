<?php
declare(strict_types=1);

namespace Cluion\Turing\Tests\Core\Pow;

use Cluion\Turing\Core\Pow\Pbkdf2Solver;
use Cluion\Turing\Core\Token\TokenEncoder;
use PHPUnit\Framework\TestCase;

final class Pbkdf2SolverTest extends TestCase
{
    /**
     * The counter whose derived key matches keySignature verifies; a
     * neighbouring counter does not.
     */
    public function test_verify_accepts_counter_matching_signature(): void
    {
        $solver = new Pbkdf2Solver();
        $salt = 'salt';
        $nonce = 'nonce';
        $cost = 100;
        // Server precomputes the signature for the correct counter.
        // ENCODING CONTRACT: keySignature is base64url of the raw derived key.
        $good = 42;
        $derived = hash_pbkdf2('sha256', $nonce . $good, $salt, $cost, 0, true);
        $keySignature = TokenEncoder::base64UrlEncode($derived);

        self::assertTrue($solver->verify(
            ['salt' => $salt, 'nonce' => $nonce, 'cost' => $cost, 'keySignature' => $keySignature],
            $good
        ));
        self::assertFalse($solver->verify(
            ['salt' => $salt, 'nonce' => $nonce, 'cost' => $cost, 'keySignature' => $keySignature],
            $good + 1
        ));
    }

    /**
     * Algorithm id is PBKDF2-SHA256.
     */
    public function test_algorithm_is_pbkdf2(): void
    {
        self::assertSame('PBKDF2-SHA256', (new Pbkdf2Solver())->algorithm());
    }
}
