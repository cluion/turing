<?php
declare(strict_types=1);

namespace Cluion\Turing\Core\Pow;

/**
 * Named difficulty bands for PoW. Integrators pick interactive / balanced /
 * strict without hand-tuning cost/maxcounter; explicit numeric keys in the
 * type config still win over the band defaults.
 */
final class PowProfile
{
    public const INTERACTIVE = 'interactive';
    public const BALANCED = 'balanced';
    public const STRICT = 'strict';

    /**
     * PBKDF2-SHA256 Deterministic defaults per profile.
     *
     * @var array<string, array{cost: int, maxcounter: int}>
     */
    private const PBKDF2 = [
        self::INTERACTIVE => ['cost' => 1000, 'maxcounter' => 2500],
        self::BALANCED => ['cost' => 5000, 'maxcounter' => 10000],
        self::STRICT => ['cost' => 15000, 'maxcounter' => 25000],
    ];

    /**
     * SHA-256 leading-zero-bit defaults per profile.
     *
     * @var array<string, array{difficulty_bits: int}>
     */
    private const SHABIT = [
        self::INTERACTIVE => ['difficulty_bits' => 16],
        self::BALANCED => ['difficulty_bits' => 20],
        self::STRICT => ['difficulty_bits' => 22],
    ];

    /**
     * Merge profile defaults into a pow type-config array. Unknown profile
     * names fall back to balanced. Keys already set in $typeConfig (cost,
     * maxcounter, difficulty_bits, …) are left unchanged.
     *
     * @param array<string, mixed> $typeConfig
     * @return array<string, mixed>
     */
    public static function apply(array $typeConfig): array
    {
        $name = self::normalize((string) ($typeConfig['profile'] ?? self::BALANCED));
        $algorithm = (string) ($typeConfig['algorithm'] ?? 'PBKDF2-SHA256');

        $defaults = match ($algorithm) {
            'SHA-256' => self::SHABIT[$name],
            default => self::PBKDF2[$name],
        };

        // Profile fills gaps only — explicit config values win.
        foreach ($defaults as $key => $value) {
            if (!array_key_exists($key, $typeConfig) || $typeConfig[$key] === null || $typeConfig[$key] === '') {
                $typeConfig[$key] = $value;
            }
        }

        $typeConfig['profile'] = $name;
        $typeConfig['algorithm'] = $algorithm;

        return $typeConfig;
    }

    /**
     * List known profile names (for docs / validation).
     *
     * @return list<string>
     */
    public static function names(): array
    {
        return [self::INTERACTIVE, self::BALANCED, self::STRICT];
    }

    /**
     * Map an arbitrary string to a known profile; unknown → balanced.
     */
    public static function normalize(string $name): string
    {
        $name = strtolower(trim($name));
        return isset(self::PBKDF2[$name]) ? $name : self::BALANCED;
    }
}
