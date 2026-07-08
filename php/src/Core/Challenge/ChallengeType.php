<?php
declare(strict_types=1);

namespace Cluion\Turing\Core\Challenge;

use Cluion\Turing\Core\KeyRing;

/**
 * A pluggable challenge kind (math, text, pow, ...). Each type knows how to
 * build and sign a fresh challenge; verification of the returned answer is
 * type-specific and done by the type or the facade.
 */
interface ChallengeType
{
    /**
     * Build and sign a fresh challenge, returning image and/or params for the client.
     */
    public function issue(array $typeConfig, KeyRing $ring, int $now): Challenge;

    /**
     * Return the wire name of this challenge type (e.g. "math").
     */
    public function name(): string;
}
