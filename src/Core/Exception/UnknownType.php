<?php
declare(strict_types=1);

namespace Cluion\Turing\Core\Exception;

use RuntimeException;

/** Thrown when a challenge type name is not registered. */
final class UnknownType extends RuntimeException implements TuringException
{
}
