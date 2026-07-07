<?php
declare(strict_types=1);

namespace Cluion\Turing\Core\Exception;

use RuntimeException;

/** Thrown when a submitted answer or PoW counter fails the challenge. */
final class ChallengeMismatch extends RuntimeException implements TuringException
{
}
