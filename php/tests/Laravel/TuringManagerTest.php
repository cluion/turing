<?php
declare(strict_types=1);

namespace Cluion\Turing\Tests\Laravel;

use Cluion\Turing\Core\Challenge\Challenge;
use Cluion\Turing\Core\Pow\Pbkdf2Solver;
use Cluion\Turing\Core\Token\TokenEncoder;
use Cluion\Turing\Laravel\TuringManager;

final class TuringManagerTest extends TestCase
{
    /**
     * The manager issues a Challenge for the default type.
     */
    public function test_challenge_returns_challenge_for_default_type(): void
    {
        $manager = $this->app->make(TuringManager::class);
        $ch = $manager->challenge();
        self::assertInstanceOf(Challenge::class, $ch);
        self::assertSame('pow', $ch->type);
        self::assertNotEmpty($ch->token);
    }

    /**
     * A PoW challenge issued by the manager verifies with the correct counter.
     */
    public function test_issue_then_verify_round_trips(): void
    {
        // Small cost/maxcounter keep the brute-force fast in tests.
        $this->app['config']->set('turing.types.pow.cost', 50);
        $this->app['config']->set('turing.types.pow.maxcounter', 200);

        $manager = $this->app->make(TuringManager::class);
        $ch = $manager->challenge('pow');

        $solver = new Pbkdf2Solver();
        $counter = 0;
        for ($c = 1; $c <= 200; $c++) {
            if ($solver->verify($ch->params, $c)) {
                $counter = $c;
                break;
            }
        }
        $packed = TokenEncoder::base64UrlEncode(
            json_encode(['t' => $ch->token, 'a' => (string) $counter], JSON_THROW_ON_ERROR)
        );
        self::assertTrue($manager->verify($packed));
    }

    /**
     * A missing secret is rejected at build time.
     */
    public function test_missing_secret_throws(): void
    {
        $this->app['config']->set('turing.secret', '');
        $this->expectException(\RuntimeException::class);
        $this->app->make(TuringManager::class)->challenge();
    }
}
