<?php
declare(strict_types=1);

namespace Cluion\Turing\Core\Challenge;

use Cluion\Turing\Core\Exception\PowAlgorithmUnsupported;
use Cluion\Turing\Core\KeyRing;
use Cluion\Turing\Core\Pow\Pbkdf2Solver;
use Cluion\Turing\Core\Pow\PowProfile;
use Cluion\Turing\Core\Pow\PowSolver;
use Cluion\Turing\Core\Pow\ShaBitSolver;
use Cluion\Turing\Core\Token\TokenEncoder;

/**
 * PoW challenge type. PBKDF2-SHA256 (Deterministic: the server picks a target
 * counter and embeds keySignature; the client brute-forces it and the server
 * verifies with a single KDF run) or SHA-256 leading-zero-bit (opt-in). A true
 * probabilistic PBKDF2 mode is not implemented, so no effort-mode toggle is
 * advertised - the algorithm choice alone determines the difficulty style.
 */
final class PowType implements ChallengeType
{
    use IssuesChallenge;

    /**
     * Return the wire name of this challenge type.
     */
    public function name(): string
    {
        return 'pow';
    }

    /**
     * Build the challenge params for the requested algorithm, sign them into a
     * token, and hand the same params to the client (no image for PoW).
     */
    public function issue(array $typeConfig, KeyRing $ring, int $now): Challenge
    {
        // Named bands (interactive / balanced / strict); explicit cost etc. still win.
        $typeConfig = PowProfile::apply($typeConfig);
        $algorithm = $typeConfig['algorithm'] ?? 'PBKDF2-SHA256';
        $salt = TokenEncoder::base64UrlEncode(random_bytes(16));
        $nonce = TokenEncoder::base64UrlEncode(random_bytes(16));
        $expire = $typeConfig['expire'] ?? 120;

        // Note: $nonce here is the KDF nonce embedded in params; the token's
        // single-use nonce is generated separately inside signChallenge().
        $params = match ($algorithm) {
            'PBKDF2-SHA256' => $this->buildPbkdf2($salt, $nonce, $typeConfig),
            'SHA-256' => $this->buildShaBit($salt, $typeConfig),
            default => throw new PowAlgorithmUnsupported("Unknown PoW algorithm: $algorithm"),
        };

        return $this->signChallenge(
            type: $this->name(),
            data: $params,
            ring: $ring,
            now: $now,
            expire: $expire,
            image: null,
            params: $params,
        );
    }

    /**
     * Build Deterministic PBKDF2 params: choose a target counter and embed its
     * derived key as keySignature (base64url of the raw bytes).
     */
    private function buildPbkdf2(string $salt, string $nonce, array $cfg): array
    {
        $cost = (int) ($cfg['cost'] ?? 5000);
        $maxcounter = (int) ($cfg['maxcounter'] ?? 10000);
        $target = random_int(1, max(1, $maxcounter));
        // Raw binary must never enter the token payload (json_encode would fail
        // on non-UTF-8 bytes); base64url is the cross-language wire convention.
        $keySignature = TokenEncoder::base64UrlEncode(
            hash_pbkdf2('sha256', $nonce . $target, $salt, $cost, 0, true)
        );
        return [
            'algorithm' => 'PBKDF2-SHA256',
            'salt' => $salt,
            'nonce' => $nonce,
            'cost' => $cost,
            'keySignature' => $keySignature,
        ];
    }

    /**
     * Build hashcash-style SHA-256 params carrying the required difficulty.
     */
    private function buildShaBit(string $salt, array $cfg): array
    {
        return [
            'algorithm' => 'SHA-256',
            'salt' => $salt,
            'difficulty_bits' => (int) ($cfg['difficulty_bits'] ?? 20),
        ];
    }

    /**
     * Resolve the solver that verifies a counter for the given algorithm.
     */
    public function solverFor(string $algorithm): PowSolver
    {
        return match ($algorithm) {
            'PBKDF2-SHA256' => new Pbkdf2Solver(),
            'SHA-256' => new ShaBitSolver(),
            default => throw new PowAlgorithmUnsupported("Unknown PoW algorithm: $algorithm"),
        };
    }
}
