<?php
declare(strict_types=1);

namespace Cluion\Turing\Core\Challenge;

use Cluion\Turing\Core\KeyRing;
use Cluion\Turing\Core\Token\Payload;
use Cluion\Turing\Core\Token\Token;
use Cluion\Turing\Core\Token\TokenEncoder;

/**
 * Shared token assembly for challenge types. Generates a single-use nonce,
 * signs a payload of the given data, and returns a Challenge that also exposes
 * the nonce so the facade can register it without re-decoding the token.
 */
trait IssuesChallenge
{
    /**
     * Sign $data into a token and assemble the Challenge envelope.
     */
    protected function signChallenge(
        string $type,
        array $data,
        KeyRing $ring,
        int $now,
        int $expire,
        ?string $image,
        ?array $params,
    ): Challenge {
        $nonce = TokenEncoder::base64UrlEncode(random_bytes(16));
        $payload = new Payload(
            type: $type,
            kid: $ring->defaultKid(),
            nonce: $nonce,
            iat: $now,
            exp: $now + $expire,
            data: $data,
        );
        $token = (string) Token::sign($payload, $ring->signer());

        return new Challenge(
            token: $token,
            image: $image,
            params: $params,
            type: $type,
            expires: $now + $expire,
            nonce: $nonce,
        );
    }
}
