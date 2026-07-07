<?php
declare(strict_types=1);

namespace Cluion\Turing\Core\Challenge;

use Cluion\Turing\Core\Charset\Charset;
use Cluion\Turing\Core\Image\ImageRenderer;
use Cluion\Turing\Core\Image\SvgRenderer;
use Cluion\Turing\Core\KeyRing;
use Cluion\Turing\Core\Token\Payload;
use Cluion\Turing\Core\Token\Token;
use Cluion\Turing\Core\Token\TokenEncoder;

/**
 * Text captcha: renders a random charset string as an image; the answer is the
 * string itself (case-insensitive). Stored as a peppered HMAC, never cleartext.
 */
final class TextType implements ChallengeType, AnswerVerifier
{
    use AnswerHash;

    /**
     * $fixedText pins the secret for deterministic tests; null means random.
     */
    public function __construct(
        private readonly string $pepper,
        private readonly Charset $charset,
        private readonly ImageRenderer $renderer = new SvgRenderer(),
        private readonly ?string $fixedText = null,
    ) {
    }

    /**
     * Return the wire name of this challenge type.
     */
    public function name(): string
    {
        return 'text';
    }

    /**
     * Generate a random string, render it, and sign a token carrying only the
     * hashed answer. params stays null (no client leakage).
     */
    public function issue(array $typeConfig, KeyRing $ring, int $now): Challenge
    {
        $text = $this->fixedText ?? $this->charset->generate($typeConfig['length'] ?? 5);
        $image = $this->renderer->render($text);
        $ah = $this->hashAnswer($text);

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
