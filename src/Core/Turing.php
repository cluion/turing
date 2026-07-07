<?php
declare(strict_types=1);

namespace Cluion\Turing\Core;

use Cluion\Turing\Core\Challenge\AnswerVerifier;
use Cluion\Turing\Core\Challenge\Challenge;
use Cluion\Turing\Core\Challenge\ChallengeType;
use Cluion\Turing\Core\Challenge\PowType;
use Cluion\Turing\Core\Exception\AlreadyUsed;
use Cluion\Turing\Core\Exception\ChallengeMismatch;
use Cluion\Turing\Core\Exception\TokenExpired;
use Cluion\Turing\Core\Exception\UnknownType;
use Cluion\Turing\Core\Store\Store;
use Cluion\Turing\Core\Token\Payload;
use Cluion\Turing\Core\Token\Token;
use Cluion\Turing\Core\Token\TokenEncoder;

/**
 * Top-level Core facade. Framework integration layers wrap this. verify()
 * enforces signature -> expiry -> single-use -> answer/counter match, and
 * delegates the answer/counter check back to the challenge type.
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
        $this->store->remember($this->nonceOf($ch->token), $cfg['expire'] ?? 120);
        return $ch;
    }

    /**
     * Verify a packed {t, a} field: decode, check expiry, consume the nonce,
     * then dispatch to the type's answer or PoW check. Returns true on success.
     */
    public function verify(string $packedToken, ?string $answer = null): bool
    {
        $unpacked = json_decode(TokenEncoder::base64UrlDecode($packedToken), true, 512, JSON_THROW_ON_ERROR);
        $token = $unpacked['t'] ?? '';
        $a = $answer ?? (string) ($unpacked['a'] ?? '');

        // v1 uses a single kid, so the default signer verifies the token.
        $payload = Token::decode($token, $this->ring->signer());

        if ($payload->exp <= ($this->config->now)()) {
            throw new TokenExpired('Token has expired.');
        }
        // Consume before comparing: a wrong answer still burns the nonce. This
        // is an accepted v1 tradeoff (mild DoS surface) for a simple ordering.
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
     * Decode a freshly signed token just to read its nonce.
     */
    private function nonceOf(string $token): string
    {
        return Token::decode($token, $this->ring->signer())->nonce;
    }

    /**
     * Delegate answer checking to the type (which owns the pepper), throwing
     * ChallengeMismatch on a wrong answer.
     */
    private function checkAnswer(Payload $p, string $answer): bool
    {
        $type = $this->type($p->type);
        if (!$type instanceof AnswerVerifier) {
            throw new UnknownType("Type {$p->type} cannot verify answers.");
        }
        if ($type->verifyAgainst($answer, (string) ($p->data['ah'] ?? ''))) {
            return true;
        }
        throw new ChallengeMismatch('Answer does not match.');
    }

    /**
     * Resolve the PoW solver for the token's algorithm and verify the counter,
     * throwing ChallengeMismatch when it does not satisfy the challenge.
     */
    private function checkPow(Payload $p, int $counter): bool
    {
        $pow = $this->type('pow');
        if (!$pow instanceof PowType) {
            throw new UnknownType('PoW type is not registered as PowType.');
        }
        $solver = $pow->solverFor((string) ($p->data['algorithm'] ?? ''));
        if ($solver->verify($p->data, $counter)) {
            return true;
        }
        throw new ChallengeMismatch('PoW counter does not satisfy the challenge.');
    }
}
