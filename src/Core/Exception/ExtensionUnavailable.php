<?php
declare(strict_types=1);

namespace Cluion\Turing\Core\Exception;

use RuntimeException;

/** Thrown when a required PHP extension (e.g. ext-sodium) is unavailable. */
final class ExtensionUnavailable extends RuntimeException implements TuringException
{
}
