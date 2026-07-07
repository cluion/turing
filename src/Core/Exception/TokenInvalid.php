<?php
declare(strict_types=1);

namespace Cluion\Turing\Core\Exception;

use RuntimeException;

/** Thrown when a token is malformed (bad segments, base64url, or JSON). */
final class TokenInvalid extends RuntimeException implements TuringException
{
}
