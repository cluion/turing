<?php
declare(strict_types=1);

namespace Cluion\Turing\Core\Pow;

use Cluion\Turing\Core\Token\TokenEncoder;

/**
 * ALTCHA-style Deterministic PoW: the server embeds keySignature (the expected
 * derived key for the chosen counter). The client brute-forces the counter;
 * verification is a single PBKDF2 run. GPU/ASIC resistance: low.
 *
 * ENCODING CONTRACT: keySignature is ALWAYS the raw derived-key bytes as
 * base64url (raw binary cannot survive JSON encoding inside the token). This
 * solver decodes it before the constant-time compare; every language port must
 * use the same base64url convention (see tests/vectors).
 */
final class Pbkdf2Solver implements PowSolver
{
    /**
     * Derive the key for the claimed counter and compare it, in constant time,
     * to the decoded keySignature.
     */
    public function verify(array $d, int $counter): bool
    {
        $salt = $d['salt'] ?? '';
        $nonce = $d['nonce'] ?? '';
        $cost = (int) ($d['cost'] ?? 0);
        $expected = TokenEncoder::base64UrlDecode((string) ($d['keySignature'] ?? ''));
        if ($expected === '') {
            return false;
        }
        $derived = hash_pbkdf2('sha256', $nonce . $counter, $salt, $cost, strlen($expected), true);
        return hash_equals($expected, $derived);
    }

    /**
     * Return the algorithm id this solver handles.
     */
    public function algorithm(): string
    {
        return 'PBKDF2-SHA256';
    }
}
