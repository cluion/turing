<?php
declare(strict_types=1);

namespace Cluion\Turing\Core;

use Cluion\Turing\Core\Challenge\AnswerVerifier;
use Cluion\Turing\Core\Challenge\Challenge;
use Cluion\Turing\Core\Challenge\ChallengeType;
use Cluion\Turing\Core\Challenge\PowType;
use Cluion\Turing\Core\Exception\AlreadyUsed;
use Cluion\Turing\Core\Exception\TokenExpired;
use Cluion\Turing\Core\Exception\TokenInvalid;
use Cluion\Turing\Core\Exception\UnknownType;
use Cluion\Turing\Core\Store\Store;
use Cluion\Turing\Core\Token\Payload;
use Cluion\Turing\Core\Token\Signer;
use Cluion\Turing\Core\Token\Token;
use Cluion\Turing\Core\Token\TokenEncoder;

/**
 * Top-level Core facade. Framework integration layers wrap this. verify()
 * returns true/false for a right/wrong answer and throws a TuringException for
 * a structurally invalid token (malformed, expired, replayed, unknown type).
 */
final class Turing
{
    /**
     * @param array<string, ChallengeType> $types
     */
    public function __construct(
        private readonly KeyRing $ring,
        private readonly Store $store,
        private readonly Config $config,
        private readonly array $types,
    ) {
    }

    /**
     * Issue a fresh challenge of the given type (or the configured default) and
     * remember its nonce for single-use tracking.
     */
    public function challenge(?string $type = null): Challenge
    {
        $type ??= $this->config->defaultType;
        $ct = $this->type($type);
        $cfg = $this->config->types[$type] ?? [];
        $ch = $ct->issue($cfg, $this->ring, ($this->config->now)());
        $this->store->remember($ch->nonce, $cfg['expire'] ?? 120);
        return $ch;
    }

    /**
     * Verify a packed {t, a} field. Returns true on a correct answer/counter,
     * false on a wrong one. Throws TokenInvalid/TokenExpired/AlreadyUsed/
     * SignatureInvalid/UnknownType for a structurally invalid submission.
     */
    public function verify(string $packedToken, ?string $answer = null): bool
    {
        try {
            $unpacked = json_decode(TokenEncoder::base64UrlDecode($packedToken), true, 512, JSON_THROW_ON_ERROR);
        } catch (\InvalidArgumentException | \JsonException $e) {
            throw new TokenInvalid('Packed token is not valid base64url JSON.', 0, $e);
        }
        if (!is_array($unpacked)) {
            throw new TokenInvalid('Packed token must decode to an object.');
        }
        $token = (string) ($unpacked['t'] ?? '');
        $a = $answer ?? (string) ($unpacked['a'] ?? '');

        $payload = Token::decode($token, $this->signerForToken($token));

        if ($payload->exp <= ($this->config->now)()) {
            throw new TokenExpired('Token has expired.');
        }
        // Consume before comparing: a wrong answer still burns the nonce. This
        // is an accepted tradeoff (mild DoS surface) for a simple ordering.
        if (!$this->store->consume($payload->nonce)) {
            throw new AlreadyUsed('Challenge already used or unknown.');
        }

        return match ($payload->type) {
            'math', 'text' => $this->checkAnswer($payload, $a),
            'pow' => $this->checkPow($payload, (int) $a),
            default => throw new UnknownType("Unknown challenge type: {$payload->type}"),
        };
    }

    /**
     * Resolve a registered challenge type instance by name.
     */
    private function type(string $name): ChallengeType
    {
        return $this->types[$name] ?? throw new UnknownType("Unknown challenge type: $name");
    }

    /**
     * Select the signer by peeking the token's (unverified) kid, so KeyRing
     * rotation works: a token signed under an older kid still verifies while a
     * new kid signs fresh ones. Token::decode then verifies with the chosen
     * signer, so a tampered kid merely fails verification. Falls back to the
     * default signer when the kid cannot be read or is unknown.
     */
    private function signerForToken(string $compact): Signer
    {
        $parts = explode('.', $compact);
        if (count($parts) === 2) {
            try {
                $data = json_decode(TokenEncoder::base64UrlDecode($parts[0]), true);
                if (is_array($data) && isset($data['kid']) && is_string($data['kid'])) {
                    return $this->ring->signer($data['kid']);
                }
            } catch (\Throwable) {
                // Fall through: Token::decode surfaces the real error next.
            }
        }
        return $this->ring->signer();
    }

    /**
     * Delegate the answer check to the type, which owns the pepper. Returns
     * false on a wrong answer (not an exception).
     */
    private function checkAnswer(Payload $p, string $answer): bool
    {
        $type = $this->type($p->type);
        if (!$type instanceof AnswerVerifier) {
            throw new UnknownType("Type {$p->type} cannot verify answers.");
        }
        return $type->verifyAgainst($answer, (string) ($p->data['ah'] ?? ''));
    }

    /**
     * Resolve the PoW solver for the token's algorithm and verify the counter.
     * Returns false when it does not satisfy the challenge (not an exception).
     */
    private function checkPow(Payload $p, int $counter): bool
    {
        $pow = $this->type('pow');
        if (!$pow instanceof PowType) {
            throw new UnknownType('PoW type is not registered as PowType.');
        }
        return $pow->solverFor((string) ($p->data['algorithm'] ?? ''))->verify($p->data, $counter);
    }
}
