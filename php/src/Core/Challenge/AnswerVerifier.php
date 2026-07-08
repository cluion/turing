<?php
declare(strict_types=1);

namespace Cluion\Turing\Core\Challenge;

/**
 * A challenge type whose answer is checked against an embedded answer hash.
 * Implemented by text-like types (math, text) so the facade can verify without
 * duplicating the hashing logic or knowing the pepper.
 */
interface AnswerVerifier
{
    /**
     * Constant-time check of a user answer against an expected answer hash.
     */
    public function verifyAgainst(string $userInput, string $expectedAh): bool;
}
