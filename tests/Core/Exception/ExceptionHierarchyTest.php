<?php
declare(strict_types=1);

namespace Cluion\Turing\Tests\Core\Exception;

use Cluion\Turing\Core\Exception\TuringException;
use Cluion\Turing\Core\Exception\TokenInvalid;
use Cluion\Turing\Core\Exception\TokenExpired;
use Cluion\Turing\Core\Exception\SignatureInvalid;
use Cluion\Turing\Core\Exception\AlreadyUsed;
use Cluion\Turing\Core\Exception\ChallengeMismatch;
use Cluion\Turing\Core\Exception\UnknownType;
use Cluion\Turing\Core\Exception\PowAlgorithmUnsupported;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ExceptionHierarchyTest extends TestCase
{
    /**
     * Every typed exception is both a TuringException (catchable as a family)
     * and a RuntimeException.
     */
    #[DataProvider('exceptions')]
    public function test_all_implement_turing_exception(string $class): void
    {
        $e = new $class('msg');
        self::assertInstanceOf(TuringException::class, $e);
        self::assertInstanceOf(\RuntimeException::class, $e);
    }

    /** @return list<array{class-string}> */
    public static function exceptions(): array
    {
        return [
            [TokenInvalid::class],
            [TokenExpired::class],
            [SignatureInvalid::class],
            [AlreadyUsed::class],
            [ChallengeMismatch::class],
            [UnknownType::class],
            [PowAlgorithmUnsupported::class],
        ];
    }
}
