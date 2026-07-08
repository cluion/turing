<?php
declare(strict_types=1);

namespace Cluion\Turing\Tests\Laravel;

use Cluion\Turing\Core\Challenge\Challenge;
use Cluion\Turing\Core\Token\TokenEncoder;
use Cluion\Turing\Laravel\Facade\Turing;
use Illuminate\Http\Request;

final class FacadeTest extends TestCase
{
    /**
     * The facade issues a challenge through the bound manager.
     */
    public function test_facade_challenge_returns_challenge(): void
    {
        self::assertInstanceOf(Challenge::class, Turing::challenge('math'));
    }

    /**
     * verifyRequest reads the packed field from the request; a wrong answer
     * returns false (not an exception).
     */
    public function test_facade_verify_request_reads_field(): void
    {
        $ch = Turing::challenge('math');
        $packed = TokenEncoder::base64UrlEncode(
            json_encode(['t' => $ch->token, 'a' => 'definitely-wrong'], JSON_THROW_ON_ERROR)
        );
        $request = Request::create('/submit', 'POST', ['turing_token' => $packed]);
        self::assertFalse(Turing::verifyRequest($request));
    }
}
