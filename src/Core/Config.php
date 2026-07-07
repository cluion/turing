<?php
declare(strict_types=1);

namespace Cluion\Turing\Core;

/**
 * Immutable runtime config for the Core facade. `now` is a Closure so callers
 * (and tests) control the clock; `types` holds per-type config arrays keyed by
 * challenge name (e.g. expire, length, algorithm).
 */
final readonly class Config
{
    /**
     * @param array<string, array<string, mixed>> $types
     * @param \Closure():int $now
     */
    public function __construct(
        public string $defaultType,
        public array $types,
        public \Closure $now,
    ) {
    }
}
