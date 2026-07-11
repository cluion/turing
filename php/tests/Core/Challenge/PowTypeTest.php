<?php
declare(strict_types=1);

namespace Cluion\Turing\Tests\Core\Challenge;

use Cluion\Turing\Core\Challenge\PowType;
use Cluion\Turing\Core\Exception\PowAlgorithmUnsupported;
use Cluion\Turing\Core\KeyRing;
use Cluion\Turing\Core\Pow\Pbkdf2Solver;
use Cluion\Turing\Core\Pow\ShaBitSolver;
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

    /**
     * The SHA-256 (hashcash) algorithm issues difficulty_bits params, no image.
     */
    public function test_issue_shabit_returns_difficulty(): void
    {
        $type = new PowType();
        $ch = $type->issue(
            ['algorithm' => 'SHA-256', 'difficulty_bits' => 12, 'expire' => 120],
            $this->ring(),
            now: 1000
        );
        self::assertNull($ch->image);
        self::assertSame('SHA-256', $ch->params['algorithm']);
        self::assertSame(12, $ch->params['difficulty_bits']);
    }

    /**
     * A named profile supplies cost when the integrator does not set one.
     */
    public function test_issue_uses_profile_defaults(): void
    {
        $type = new PowType();
        $ch = $type->issue(
            ['algorithm' => 'PBKDF2-SHA256', 'profile' => 'interactive', 'expire' => 120],
            $this->ring(),
            now: 1000
        );
        self::assertSame(1000, $ch->params['cost']);
    }

    /**
     * solverFor returns the matching solver for each supported algorithm.
     */
    public function test_solver_for_returns_matching_solver(): void
    {
        $type = new PowType();
        self::assertInstanceOf(Pbkdf2Solver::class, $type->solverFor('PBKDF2-SHA256'));
        self::assertInstanceOf(ShaBitSolver::class, $type->solverFor('SHA-256'));
    }

    /**
     * An unknown algorithm is rejected on both issue and solverFor.
     */
    public function test_unknown_algorithm_throws(): void
    {
        $type = new PowType();
        $this->expectException(PowAlgorithmUnsupported::class);
        $type->solverFor('bogus');
    }

    /**
     * issue() with an unknown algorithm also raises PowAlgorithmUnsupported.
     */
    public function test_issue_unknown_algorithm_throws(): void
    {
        $type = new PowType();
        $this->expectException(PowAlgorithmUnsupported::class);
        $type->issue(['algorithm' => 'bogus'], $this->ring(), now: 1000);
    }
}
