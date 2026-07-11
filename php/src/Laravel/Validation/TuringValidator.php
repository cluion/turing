<?php
declare(strict_types=1);

namespace Cluion\Turing\Laravel\Validation;

use Cluion\Turing\Core\Exception\AlreadyUsed;
use Cluion\Turing\Core\Exception\SignatureInvalid;
use Cluion\Turing\Core\Exception\TokenExpired;
use Cluion\Turing\Core\Exception\TokenInvalid;
use Cluion\Turing\Core\Exception\TuringException;
use Cluion\Turing\Core\Exception\UnknownType;
use Cluion\Turing\Laravel\TuringManager;
use Psr\Log\LoggerInterface;

/**
 * Body of the 'turing' validation rule. Delegates to the manager and converts
 * a structural TuringException into a plain validation failure, logging a
 * stable machine-readable code without leaking details to the client.
 */
final class TuringValidator
{
    /**
     * Hold the manager and an optional logger for failure diagnostics.
     */
    public function __construct(
        private readonly TuringManager $manager,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * Return true only for a correct answer; false for a wrong answer or any
     * structural token error (which is logged, not surfaced).
     */
    public function validate(string $packed): bool
    {
        try {
            $ok = $this->manager->verify($packed);
            if (!$ok) {
                $this->logger?->info('turing verification rejected', ['code' => 'wrong_answer']);
            }
            return $ok;
        } catch (TuringException $e) {
            $this->logger?->info('turing verification rejected', [
                'code' => self::codeFor($e),
                // Class name for operators; do not expose to end users.
                'exception' => $e::class,
            ]);
            return false;
        }
    }

    /**
     * Stable failure codes for metrics / log aggregation (not user-facing).
     */
    public static function codeFor(TuringException $e): string
    {
        return match (true) {
            $e instanceof TokenExpired => 'expired',
            $e instanceof AlreadyUsed => 'already_used',
            $e instanceof SignatureInvalid => 'signature_invalid',
            $e instanceof UnknownType => 'unknown_type',
            $e instanceof TokenInvalid => 'invalid',
            default => 'rejected',
        };
    }
}
