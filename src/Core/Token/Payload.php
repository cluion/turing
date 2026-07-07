<?php
declare(strict_types=1);

namespace Cluion\Turing\Core\Token;

/**
 * Immutable token payload. Serialized to canonical JSON (via TokenEncoder)
 * and signed by a Signer. The `data` shape depends on `type` (see challenge types).
 */
final readonly class Payload
{
    public function __construct(
        public string $type,
        public string $kid,
        public string $nonce,
        public int $iat,
        public int $exp,
        public array $data,
        public int $v = 1,
    ) {
    }

    /** Ordered associative form fed to canonical JSON encoding. */
    public function toArray(): array
    {
        return [
            'v' => $this->v,
            'type' => $this->type,
            'kid' => $this->kid,
            'nonce' => $this->nonce,
            'iat' => $this->iat,
            'exp' => $this->exp,
            'data' => $this->data,
        ];
    }

    /** Rebuild a Payload from a decoded array, defaulting version to 1. */
    public static function fromArray(array $a): self
    {
        return new self(
            type: $a['type'],
            kid: $a['kid'],
            nonce: $a['nonce'],
            iat: $a['iat'],
            exp: $a['exp'],
            data: $a['data'] ?? [],
            v: $a['v'] ?? 1,
        );
    }
}
