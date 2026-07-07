<?php
declare(strict_types=1);

namespace Cluion\Turing\Core\Challenge;

use Cluion\Turing\Core\Charset\Charset;
use Cluion\Turing\Core\Image\ImageRenderer;
use Cluion\Turing\Core\Image\SvgRenderer;
use Cluion\Turing\Core\KeyRing;

/**
 * Text captcha: renders a random charset string as an image; the answer is the
 * string itself (case-insensitive). Stored as a peppered HMAC, never cleartext.
 */
final class TextType implements ChallengeType, AnswerVerifier
{
    use AnswerHash;
    use IssuesChallenge;

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

        return $this->signChallenge(
            type: $this->name(),
            data: ['ah' => $ah],
            ring: $ring,
            now: $now,
            expire: $typeConfig['expire'] ?? 120,
            image: $image,
            params: null,
        );
    }
}
