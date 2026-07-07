<?php
declare(strict_types=1);

namespace Cluion\Turing\Tests\Core;

use Cluion\Turing\Core\Challenge\MathType;
use Cluion\Turing\Core\Challenge\PowType;
use Cluion\Turing\Core\Challenge\TextType;
use Cluion\Turing\Core\Charset\DefaultCharset;
use Cluion\Turing\Core\Config;
use Cluion\Turing\Core\Exception\AlreadyUsed;
use Cluion\Turing\Core\Exception\ChallengeMismatch;
use Cluion\Turing\Core\Exception\TokenExpired;
use Cluion\Turing\Core\Exception\UnknownType;
use Cluion\Turing\Core\KeyRing;
use Cluion\Turing\Core\Pow\Pbkdf2Solver;
use Cluion\Turing\Core\Store\NullStore;
use Cluion\Turing\Core\Store\Store;
use Cluion\Turing\Core\Token\HmacSigner;
use Cluion\Turing\Core\Token\TokenEncoder;
use Cluion\Turing\Core\Turing;
use PHPUnit\Framework\TestCase;

final class TuringFacadeTest extends TestCase
{
    /**
     * Build a facade wired with all three challenge types. $now is a Closure
     * so tests can advance the clock; $fixedMath pins the math answer.
     */
    private function facade(Store $store, \Closure $now, ?array $fixedMath = null): Turing
    {
        $ring = (new KeyRing('k1'))->add('k1', new HmacSigner('secret'));
        $config = new Config(
            defaultType: 'math',
            types: [
                'math' => ['expire' => 120],
                'text' => ['expire' => 120, 'length' => 5],
                'pow'  => ['algorithm' => 'PBKDF2-SHA256', 'cost' => 50, 'maxcounter' => 100, 'expire' => 120],
            ],
            now: $now,
        );
        return new Turing(
            ring: $ring,
            store: $store,
            config: $config,
            types: [
                'math' => new MathType(pepper: 'pepper', fixedForTest: $fixedMath),
                'text' => new TextType(pepper: 'pepper', charset: new DefaultCharset()),
                'pow'  => new PowType(),
            ],
        );
    }

    /**
     * Pack a token and answer into the single hidden-field format {t, a}.
     */
    private function pack(string $token, string $answer): string
    {
        return TokenEncoder::base64UrlEncode(json_encode(['t' => $token, 'a' => $answer], JSON_THROW_ON_ERROR));
    }

    /**
     * A correct math answer passes end to end.
     */
    public function test_math_correct_answer_passes(): void
    {
        $now = fn() => 1000;
        $turing = $this->facade(new NullStore(), $now, ['a' => 2, 'b' => 3, 'op' => '+']);
        $ch = $turing->challenge('math');
        self::assertTrue($turing->verify($this->pack($ch->token, '5')));
    }

    /**
     * A wrong math answer raises ChallengeMismatch.
     */
    public function test_math_wrong_answer_throws_mismatch(): void
    {
        $now = fn() => 1000;
        $turing = $this->facade(new NullStore(), $now, ['a' => 2, 'b' => 3, 'op' => '+']);
        $ch = $turing->challenge('math');
        $this->expectException(ChallengeMismatch::class);
        $turing->verify($this->pack($ch->token, '9'));
    }

    /**
     * A token presented after expiry raises TokenExpired.
     */
    public function test_expired_token_throws(): void
    {
        $clock = 1000;
        $now = function () use (&$clock) {
            return $clock;
        };
        $turing = $this->facade(new NullStore(), $now, ['a' => 2, 'b' => 3, 'op' => '+']);
        $ch = $turing->challenge('math');
        $clock = 2000; // past the 120s expiry
        $this->expectException(TokenExpired::class);
        $turing->verify($this->pack($ch->token, '5'));
    }

    /**
     * A single-use store rejects a replayed token with AlreadyUsed.
     */
    public function test_replay_throws_already_used(): void
    {
        $store = new class implements Store {
            /** @var array<string, true> */
            private array $live = [];

            public function remember(string $nonce, int $ttlSeconds): void
            {
                $this->live[$nonce] = true;
            }

            public function consume(string $nonce): bool
            {
                if (isset($this->live[$nonce])) {
                    unset($this->live[$nonce]);
                    return true;
                }
                return false;
            }
        };
        $now = fn() => 1000;
        $turing = $this->facade($store, $now, ['a' => 2, 'b' => 3, 'op' => '+']);
        $ch = $turing->challenge('math');
        $packed = $this->pack($ch->token, '5');
        self::assertTrue($turing->verify($packed)); // first use consumes the nonce
        $this->expectException(AlreadyUsed::class);
        $turing->verify($packed);                   // replay
    }

    /**
     * A PoW challenge round-trips: brute-force the counter, then verify passes.
     */
    public function test_pow_full_round_trip(): void
    {
        $now = fn() => 1000;
        $turing = $this->facade(new NullStore(), $now);
        $ch = $turing->challenge('pow');
        self::assertSame('pow', $ch->type);

        // Simulate the client: find the counter the server's keySignature targets.
        $solver = new Pbkdf2Solver();
        $counter = null;
        for ($c = 1; $c <= 100; $c++) {
            if ($solver->verify($ch->params, $c)) {
                $counter = $c;
                break;
            }
        }
        self::assertNotNull($counter, 'client should find a counter within maxcounter');
        self::assertTrue($turing->verify($this->pack($ch->token, (string) $counter)));
    }

    /**
     * Requesting an unregistered challenge type raises UnknownType.
     */
    public function test_challenge_unknown_type_throws(): void
    {
        $now = fn() => 1000;
        $turing = $this->facade(new NullStore(), $now);
        $this->expectException(UnknownType::class);
        $turing->challenge('bogus');
    }
}
