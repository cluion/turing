<?php
declare(strict_types=1);

namespace Cluion\Turing\Core\Pow;

/**
 * PoW effort mode. Deterministic gives predictable solve time (server embeds a
 * target); Probabilistic uses statistical difficulty (client relies on luck).
 */
enum EffortMode: string
{
    case Deterministic = 'deterministic';
    case Probabilistic = 'probabilistic';
}
