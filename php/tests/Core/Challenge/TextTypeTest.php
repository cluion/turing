<?php
declare(strict_types=1);

namespace Cluion\Turing\Tests\Core\Challenge;

use Cluion\Turing\Core\Challenge\TextType;
use Cluion\Turing\Core\Charset\DefaultCharset;
use Cluion\Turing\Core\KeyRing;
use Cluion\Turing\Core\Token\HmacSigner;
use Cluion\Turing\Core\Token\Token;
use PHPUnit\Framework\TestCase;

final class TextTypeTest extends TestCase
{
    /**
     * Build a single-key ring for signing challenge tokens.
     */
    private function ring(): KeyRing
    {
        return (new KeyRing('k1'))->add('k1', new HmacSigner('secret'));
    }

    /**
     * A fixed-text challenge round-trips, verifying case-insensitively.
     */
    public function test_issue_and_verify_round_trip(): void
    {
        $type = new TextType(pepper: 'pepper', charset: new DefaultCharset(), fixedText: 'AB23');
        $ch = $type->issue(['expire' => 120, 'length' => 4], $this->ring(), now: 1000);
        self::assertSame('text', $ch->type);
        self::assertNull($ch->params);
        $ah = Token::decode($ch->token, new HmacSigner('secret'))->data['ah'];
        self::assertTrue($type->verifyAgainst('ab23', $ah));   // case-insensitive
        self::assertFalse($type->verifyAgainst('WRONG', $ah));
    }
}
