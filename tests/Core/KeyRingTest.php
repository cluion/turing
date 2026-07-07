<?php
declare(strict_types=1);

namespace Cluion\Turing\Tests\Core;

use Cluion\Turing\Core\KeyRing;
use Cluion\Turing\Core\Token\HmacSigner;
use PHPUnit\Framework\TestCase;

final class KeyRingTest extends TestCase
{
    /**
     * The default kid is reported and resolves to its registered signer.
     */
    public function test_default_kid_and_signer_resolve(): void
    {
        $signer = new HmacSigner('s');
        $ring = (new KeyRing('k1'))->add('k1', $signer);
        self::assertSame('k1', $ring->defaultKid());
        self::assertSame($signer, $ring->signer());
        self::assertSame($signer, $ring->signer('k1'));
    }

    /**
     * Resolving an unregistered kid raises OutOfBoundsException.
     */
    public function test_unknown_kid_throws(): void
    {
        $ring = (new KeyRing('k1'))->add('k1', new HmacSigner('s'));
        $this->expectException(\OutOfBoundsException::class);
        $ring->signer('missing');
    }
}
