<?php
declare(strict_types=1);

namespace Cluion\Turing\Core\Pow;

/**
 * Verifies a claimed PoW counter against a challenge's parameters.
 */
interface PowSolver
{
    /**
     * Return true iff the claimed counter satisfies the challenge parameters.
     */
    public function verify(array $challengeData, int $counter): bool;

    /**
     * Return the algorithm id this solver handles.
     */
    public function algorithm(): string;
}
