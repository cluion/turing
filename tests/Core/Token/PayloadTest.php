<?php
declare(strict_types=1);

namespace Cluion\Turing\Tests\Core\Token;

use Cluion\Turing\Core\Token\Payload;
use PHPUnit\Framework\TestCase;

final class PayloadTest extends TestCase
{
    /** toArray then fromArray preserves every field. */
    public function test_round_trip_preserves_fields(): void
    {
        $p = new Payload(type: 'math', kid: 'k1', nonce: 'n', iat: 1000, exp: 1120, data: ['ah' => 'x']);
        $again = Payload::fromArray($p->toArray());
        self::assertSame(1, $again->v);
        self::assertSame('math', $again->type);
        self::assertSame('k1', $again->kid);
        self::assertSame(['ah' => 'x'], $again->data);
    }

    /** Version defaults to 1 when the array omits it. */
    public function test_from_array_defaults_version_to_one(): void
    {
        $p = Payload::fromArray(['type' => 'pow', 'kid' => 'k', 'nonce' => 'n', 'iat' => 1, 'exp' => 2, 'data' => []]);
        self::assertSame(1, $p->v);
    }
}
