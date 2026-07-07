<?php
declare(strict_types=1);

namespace Cluion\Turing\Core\Exception;

use RuntimeException;

/** Thrown when a challenge nonce was already consumed (replay). */
final class AlreadyUsed extends RuntimeException implements TuringException
{
}
