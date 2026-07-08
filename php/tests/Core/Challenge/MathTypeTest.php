<?php
declare(strict_types=1);

namespace Cluion\Turing\Tests\Core\Challenge;

use Cluion\Turing\Core\Challenge\MathType;
use Cluion\Turing\Core\KeyRing;
use Cluion\Turing\Core\Token\HmacSigner;
use Cluion\Turing\Core\Token\Token;
use PHPUnit\Framework\TestCase;

final class MathTypeTest extends TestCase
{
    /**
     * Build a single-key ring for signing challenge tokens.
     */
    private function ring(): KeyRing
    {
        return (new KeyRing('k1'))->add('k1', new HmacSigner('secret'));
    }

    /**
     * Read the embedded answer hash straight from the signed token.
     */
    private function answerHash(string $token): string
    {
        return Token::decode($token, new HmacSigner('secret'))->data['ah'];
    }

    /**
     * issue() returns a signed token, an SVG image, and the correct metadata.
     */
    public function test_issue_returns_token_and_image(): void
    {
        $type = new MathType(pepper: 'pepper');
        $ch = $type->issue(['expire' => 120], $this->ring(), now: 1000);
        self::assertNotEmpty($ch->token);
        self::assertStringStartsWith('<svg', $ch->image ?? '');
        self::assertSame('math', $ch->type);
        self::assertSame(1120, $ch->expires);
        // params must not leak the answer hash to the client.
        self::assertNull($ch->params);
    }

    /**
     * The correct answer verifies, trimming and case aside.
     */
    public function test_verify_accepts_correct_answer_case_insensitive(): void
    {
        $type = new MathType(pepper: 'pepper', fixedForTest: ['a' => 3, 'b' => 5, 'op' => '+']);
        $ch = $type->issue(['expire' => 120], $this->ring(), now: 1000);
        $ah = $this->answerHash($ch->token);
        // The answer to 3 + 5 is 8.
        self::assertTrue($type->verifyAgainst('8', $ah));
        self::assertTrue($type->verifyAgainst(' 8 ', $ah));
    }

    /**
     * A wrong answer does not verify.
     */
    public function test_verify_rejects_wrong_answer(): void
    {
        $type = new MathType(pepper: 'pepper', fixedForTest: ['a' => 3, 'b' => 5, 'op' => '+']);
        $ch = $type->issue(['expire' => 120], $this->ring(), now: 1000);
        $ah = $this->answerHash($ch->token);
        self::assertFalse($type->verifyAgainst('9', $ah));
    }
}
