<?php
declare(strict_types=1);

namespace Cluion\Turing\Tests\Core;

use Cluion\Turing\Core\Pow\Pbkdf2Solver;
use Cluion\Turing\Core\Pow\ShaBitSolver;
use Cluion\Turing\Core\Token\Ed25519Signer;
use Cluion\Turing\Core\Token\HmacSigner;
use Cluion\Turing\Core\Token\Payload;
use Cluion\Turing\Core\Token\Token;
use PHPUnit\Framework\TestCase;

final class VectorTest extends TestCase
{
    /**
     * Load a committed fixture from tests/vectors as an associative array.
     */
    private function load(string $name): array
    {
        $path = __DIR__ . '/../vectors/' . $name;
        return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * The HMAC token reproduces the frozen compact string byte for byte.
     */
    public function test_hmac_vector_token_matches_fixture(): void
    {
        $v = $this->load('hmac-roundtrip.json');
        $p = Payload::fromArray($v['payload']);
        $compact = (string) Token::sign($p, new HmacSigner($v['secret']));
        self::assertSame($v['expectedCompact'], $compact, 'A port must reproduce this exact token.');
    }

    /**
     * The Ed25519 token reproduces the frozen compact string (opt-in, sodium).
     */
    public function test_ed25519_vector_token_matches_fixture(): void
    {
        $v = $this->load('ed25519-roundtrip.json');
        if (($v['enabled'] ?? false) !== true || !function_exists('sodium_crypto_sign_seed_keypair')) {
            self::markTestSkipped('Ed25519 vector disabled or ext-sodium missing.');
        }
        $kp = sodium_crypto_sign_seed_keypair(hex2bin($v['seedHex']));
        $signer = new Ed25519Signer(
            sodium_crypto_sign_secretkey($kp),
            sodium_crypto_sign_publickey($kp),
        );
        $p = Payload::fromArray($v['payload']);
        self::assertSame($v['expectedCompact'], (string) Token::sign($p, $signer));
    }

    /**
     * The PBKDF2 solver accepts the fixture's correct counter.
     */
    public function test_pow_pbkdf2_vector(): void
    {
        $v = $this->load('pow-pbkdf2.json');
        self::assertTrue((new Pbkdf2Solver())->verify($v['challenge'], $v['correctCounter']));
    }

    /**
     * The SHA-bit solver accepts the fixture's correct counter.
     */
    public function test_pow_shabit_vector(): void
    {
        $v = $this->load('pow-shabit.json');
        self::assertTrue((new ShaBitSolver())->verify($v['challenge'], $v['correctCounter']));
    }
}
