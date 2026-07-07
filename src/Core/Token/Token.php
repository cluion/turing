<?php
declare(strict_types=1);

namespace Cluion\Turing\Core\Token;

use Cluion\Turing\Core\Exception\SignatureInvalid;
use Cluion\Turing\Core\Exception\TokenInvalid;

/**
 * Compact signed token: base64url(canonicalJson(payload)) "." base64url(signature).
 * The signer signs the exact canonical JSON bytes; decode verifies before trusting.
 */
final class Token
{
    private function __construct(
        public readonly Payload $payload,
        public readonly string $payloadJson,
        public readonly string $signature,
    ) {
    }

    /** Build a signed token from a payload and signer. */
    public static function sign(Payload $payload, Signer $signer): self
    {
        $json = TokenEncoder::canonicalJson($payload->toArray());
        return new self($payload, $json, $signer->sign($json));
    }

    /** Serialize to the compact two-segment string form. */
    public function __toString(): string
    {
        return TokenEncoder::base64UrlEncode($this->payloadJson)
            . '.'
            . TokenEncoder::base64UrlEncode($this->signature);
    }

    /**
     * Parse a compact token, verify its signature, and return the payload.
     * Throws TokenInvalid (malformed) or SignatureInvalid (bad signature).
     */
    public static function decode(string $compact, Signer $signer): Payload
    {
        $parts = explode('.', $compact);
        if (count($parts) !== 2) {
            throw new TokenInvalid('Token must have two segments separated by ".".');
        }
        try {
            $json = TokenEncoder::base64UrlDecode($parts[0]);
            $signature = TokenEncoder::base64UrlDecode($parts[1]);
        } catch (\InvalidArgumentException $e) {
            throw new TokenInvalid('Token segments are not valid base64url.', 0, $e);
        }
        // Verify before parsing so untrusted bytes never reach json_decode logic.
        if (!$signer->verify($json, $signature)) {
            throw new SignatureInvalid('Token signature does not match.');
        }
        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new TokenInvalid('Token payload is not valid JSON.', 0, $e);
        }
        return Payload::fromArray($data);
    }
}
