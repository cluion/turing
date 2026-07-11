<?php
declare(strict_types=1);

namespace Cluion\Turing\Tests\Laravel;

use Cluion\Turing\Core\Exception\AlreadyUsed;
use Cluion\Turing\Core\Exception\TokenExpired;
use Cluion\Turing\Core\Exception\TokenInvalid;
use Cluion\Turing\Core\Pow\Pbkdf2Solver;
use Cluion\Turing\Core\Token\TokenEncoder;
use Cluion\Turing\Laravel\TuringManager;
use Cluion\Turing\Laravel\Validation\TuringValidator;
use Illuminate\Support\Facades\Validator;

final class TuringValidatorTest extends TestCase
{
    /**
     * A correct PoW counter passes the 'turing' rule.
     */
    public function test_correct_pow_counter_passes_rule(): void
    {
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
        $validator = Validator::make(['turing_token' => $packed], ['turing_token' => 'turing']);
        self::assertTrue($validator->passes());
    }

    /**
     * A wrong PoW counter fails the rule.
     */
    public function test_wrong_counter_fails_rule(): void
    {
        $this->app['config']->set('turing.types.pow.cost', 50);
        $manager = $this->app->make(TuringManager::class);
        $ch = $manager->challenge('pow');
        $packed = TokenEncoder::base64UrlEncode(
            json_encode(['t' => $ch->token, 'a' => '0'], JSON_THROW_ON_ERROR)
        );
        $validator = Validator::make(['turing_token' => $packed], ['turing_token' => 'turing']);
        self::assertTrue($validator->fails());
    }

    /**
     * Malformed input fails the rule without surfacing an exception.
     */
    public function test_malformed_input_fails_rule(): void
    {
        $validator = Validator::make(['turing_token' => '@@@bad@@@'], ['turing_token' => 'turing']);
        self::assertTrue($validator->fails());
    }

    /**
     * Stable log codes map structural exceptions for metrics without user leak.
     */
    public function test_failure_codes_are_stable(): void
    {
        self::assertSame('expired', TuringValidator::codeFor(new TokenExpired('x')));
        self::assertSame('already_used', TuringValidator::codeFor(new AlreadyUsed('x')));
        self::assertSame('invalid', TuringValidator::codeFor(new TokenInvalid('x')));
    }
}
