<?php
declare(strict_types=1);

namespace Cluion\Turing\Core\Challenge;

use Cluion\Turing\Core\Image\ImageRenderer;
use Cluion\Turing\Core\Image\SvgRenderer;
use Cluion\Turing\Core\KeyRing;
use Cluion\Turing\Core\Token\Payload;
use Cluion\Turing\Core\Token\Token;
use Cluion\Turing\Core\Token\TokenEncoder;

/**
 * Math captcha: shows "a op b" as an image; the answer is the numeric result.
 * The answer is stored in the token as a peppered HMAC, never in cleartext.
 */
final class MathType implements ChallengeType
{
    use AnswerHash;

    /**
     * $fixedForTest pins a/b/op for deterministic tests; null means random.
     */
    public function __construct(
        private readonly string $pepper,
        private readonly ImageRenderer $renderer = new SvgRenderer(),
        private readonly ?array $fixedForTest = null,
    ) {
    }

    /**
     * Return the wire name of this challenge type.
     */
    public function name(): string
    {
        return 'math';
    }

    /**
     * Generate an arithmetic expression, render it, and sign a token that
     * carries only the hashed answer. params stays null (no client leakage).
     */
    public function issue(array $typeConfig, KeyRing $ring, int $now): Challenge
    {
        $a = $this->fixedForTest['a'] ?? random_int(1, 9);
        $b = $this->fixedForTest['b'] ?? random_int(1, 9);
        $op = $this->fixedForTest['op'] ?? (['+', '-'][random_int(0, 1)]);
        $answer = $op === '+' ? $a + $b : $a - $b;

        $image = $this->renderer->render("$a $op $b");
        $ah = $this->hashAnswer((string) $answer);

        $expire = $typeConfig['expire'] ?? 120;
        $payload = new Payload(
            type: $this->name(),
            kid: $ring->defaultKid(),
            nonce: TokenEncoder::base64UrlEncode(random_bytes(16)),
            iat: $now,
            exp: $now + $expire,
            data: ['ah' => $ah],
        );
        $token = (string) Token::sign($payload, $ring->signer());

        return new Challenge(
            token: $token,
            image: $image,
            params: null,
            type: $this->name(),
            expires: $now + $expire,
        );
    }
}
