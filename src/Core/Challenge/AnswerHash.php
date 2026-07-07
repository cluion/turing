<?php
declare(strict_types=1);

namespace Cluion\Turing\Core\Challenge;

use Cluion\Turing\Core\Token\TokenEncoder;

/**
 * Shared answer-hashing for text-like challenges. The cleartext answer is
 * never stored; only a peppered HMAC of its canonical form is embedded in the
 * token. Host classes must expose a readonly string $pepper property.
 */
trait AnswerHash
{
    /**
     * Hash an answer in canonical form (trimmed, uppercased) as base64url.
     */
    private function hashAnswer(string $answer): string
    {
        return TokenEncoder::base64UrlEncode(
            hash_hmac('sha256', trim(strtoupper($answer)), $this->pepper, true)
        );
    }

    /**
     * Constant-time check of a user answer against an expected answer hash.
     */
    public function verifyAgainst(string $userInput, string $expectedAh): bool
    {
        return hash_equals($expectedAh, $this->hashAnswer($userInput));
    }
}
