<?php
declare(strict_types=1);

namespace Cluion\Turing\Core\Exception;

use RuntimeException;

/** Thrown when a token signature does not match its payload. */
final class SignatureInvalid extends RuntimeException implements TuringException
{
}
