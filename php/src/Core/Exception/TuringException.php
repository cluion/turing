<?php
declare(strict_types=1);

namespace Cluion\Turing\Core\Exception;

/**
 * Base interface for all Turing Core exceptions. Every typed exception
 * implements this so callers can catch the whole family with one type.
 */
interface TuringException extends \Throwable
{
}
