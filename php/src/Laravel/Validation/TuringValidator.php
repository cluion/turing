<?php
declare(strict_types=1);

namespace Cluion\Turing\Laravel\Validation;

use Cluion\Turing\Core\Exception\TuringException;
use Cluion\Turing\Laravel\TuringManager;
use Psr\Log\LoggerInterface;

/**
 * Body of the 'turing' validation rule. Delegates to the manager and converts
 * a structural TuringException into a plain validation failure, logging the
 * reason without leaking it to the client.
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
            return $this->manager->verify($packed);
        } catch (TuringException $e) {
            $this->logger?->info('turing verification rejected', ['reason' => $e::class]);
            return false;
        }
    }
}
