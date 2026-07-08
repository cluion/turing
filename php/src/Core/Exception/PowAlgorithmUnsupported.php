<?php
declare(strict_types=1);

namespace Cluion\Turing\Core\Exception;

use RuntimeException;

/** Thrown for an unknown PoW algorithm or a missing native primitive. */
final class PowAlgorithmUnsupported extends RuntimeException implements TuringException
{
}
