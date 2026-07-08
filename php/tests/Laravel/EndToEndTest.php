<?php
declare(strict_types=1);

namespace Cluion\Turing\Tests\Laravel;

use Cluion\Turing\Core\Pow\Pbkdf2Solver;
use Cluion\Turing\Core\Token\TokenEncoder;
use Illuminate\Support\Facades\Validator;

final class EndToEndTest extends TestCase
{
    /**
     * A challenge fetched from the endpoint verifies through the 'turing' rule.
     */
    public function test_endpoint_challenge_verifies_through_rule(): void
    {
        $this->app['config']->set('turing.types.pow.cost', 50);
        $this->app['config']->set('turing.types.pow.maxcounter', 200);

        $challenge = $this->getJson('/turing/challenge?type=pow')->assertOk()->json();

        $solver = new Pbkdf2Solver();
        $counter = 0;
        for ($c = 1; $c <= 200; $c++) {
            if ($solver->verify($challenge['params'], $c)) {
                $counter = $c;
                break;
            }
        }
        $packed = TokenEncoder::base64UrlEncode(
            json_encode(['t' => $challenge['token'], 'a' => (string) $counter], JSON_THROW_ON_ERROR)
        );

        $validator = Validator::make(['turing_token' => $packed], ['turing_token' => 'required|turing']);
        self::assertTrue($validator->passes());
    }
}
