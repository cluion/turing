<?php
declare(strict_types=1);

namespace Cluion\Turing\Core\Exception;

use RuntimeException;

/** Thrown when a token is presented after its exp timestamp. */
final class TokenExpired extends RuntimeException implements TuringException
{
}
