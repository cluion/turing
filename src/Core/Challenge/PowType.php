<?php
declare(strict_types=1);

namespace Cluion\Turing\Core\Challenge;

use Cluion\Turing\Core\Exception\PowAlgorithmUnsupported;
use Cluion\Turing\Core\KeyRing;
use Cluion\Turing\Core\Pow\EffortMode;
use Cluion\Turing\Core\Pow\Pbkdf2Solver;
use Cluion\Turing\Core\Pow\PowSolver;
use Cluion\Turing\Core\Pow\ShaBitSolver;
use Cluion\Turing\Core\Token\Payload;
use Cluion\Turing\Core\Token\Token;
use Cluion\Turing\Core\Token\TokenEncoder;

/**
 * PoW challenge type. Default: PBKDF2-SHA256, Deterministic effort (the server
 * picks a target counter and embeds keySignature). The client brute-forces it;
 * the server verifies with a single KDF run. SHA-256 bit mode is opt-in.
 */
final class PowType implements ChallengeType
{
    /**
     * Fix the effort mode advertised in the challenge params.
     */
    public function __construct(
        private readonly EffortMode $mode = EffortMode::Deterministic,
    ) {
    }

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
        $algorithm = $typeConfig['algorithm'] ?? 'PBKDF2-SHA256';
        $salt = TokenEncoder::base64UrlEncode(random_bytes(16));
        $nonce = TokenEncoder::base64UrlEncode(random_bytes(16));
        $expire = $typeConfig['expire'] ?? 120;

        $params = match ($algorithm) {
            'PBKDF2-SHA256' => $this->buildPbkdf2($salt, $nonce, $typeConfig),
            'SHA-256' => $this->buildShaBit($salt, $typeConfig),
            default => throw new PowAlgorithmUnsupported("Unknown PoW algorithm: $algorithm"),
        };

        $payload = new Payload(
            type: $this->name(),
            kid: $ring->defaultKid(),
            nonce: TokenEncoder::base64UrlEncode(random_bytes(16)),
            iat: $now,
            exp: $now + $expire,
            data: $params,
        );
        $token = (string) Token::sign($payload, $ring->signer());

        return new Challenge(
            token: $token,
            image: null,
            params: $params,
            type: $this->name(),
            expires: $now + $expire,
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
            'mode' => $this->mode->value,
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
            'mode' => $this->mode->value,
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
