<?php
declare(strict_types=1);

namespace Cluion\Turing\Laravel\Validation;

/**
 * Body of the 'turing' rule. Real verification is wired in the validator task;
 * this stub fails any input so the rule is registered and callable.
 */
final class TuringValidator
{
    /**
     * Placeholder that fails any input until verification is implemented.
     */
    public function validate(string $packed): bool
    {
        return false;
    }
}
