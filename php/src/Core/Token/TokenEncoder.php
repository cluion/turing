<?php
declare(strict_types=1);

namespace Cluion\Turing\Core\Token;

/**
 * Low-level encoding helpers shared by every signer and the token codec.
 * Pure (no I/O, no state) so it ports trivially to other languages.
 */
final class TokenEncoder
{
    /**
     * Encode raw bytes as URL-safe base64 without padding.
     */
    public static function base64UrlEncode(string $bin): string
    {
        return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
    }

    /**
     * Decode a URL-safe base64 string back to raw bytes.
     */
    public static function base64UrlDecode(string $s): string
    {
        // Restore the padding base64_decode() expects.
        $pad = strlen($s) % 4;
        if ($pad !== 0) {
            $s .= str_repeat('=', 4 - $pad);
        }
        $bin = base64_decode(strtr($s, '-_', '+/'), true);
        if ($bin === false) {
            throw new \InvalidArgumentException('Invalid base64url input.');
        }
        return $bin;
    }

    /**
     * Deterministic JSON: keys sorted lexicographically at every depth, no
     * slash/unicode escaping. The output bytes are exactly what the signer
     * signs, so signing and verifying sides must produce identical bytes.
     */
    public static function canonicalJson(array $data): string
    {
        self::sortKeysRecursive($data);
        $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new \InvalidArgumentException('Unable to encode JSON.');
        }
        return $json;
    }

    /**
     * Sort array keys in place, recursing into nested arrays.
     */
    private static function sortKeysRecursive(array &$data): void
    {
        ksort($data);
        foreach ($data as &$value) {
            if (is_array($value)) {
                self::sortKeysRecursive($value);
            }
        }
        unset($value);
    }
}
