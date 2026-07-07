<?php
declare(strict_types=1);

namespace Cluion\Turing\Core;

use Cluion\Turing\Core\Token\Signer;

/**
 * Holds kid -> Signer mappings and the kid used to sign new tokens.
 * Supports rotation: register a new signer under a new kid while keeping
 * the old ones available for verification.
 */
final class KeyRing
{
    /** @var array<string, Signer> */
    private array $signers = [];

    /**
     * Fix the kid used when signing fresh tokens.
     */
    public function __construct(private readonly string $defaultKid)
    {
    }

    /**
     * Register a signer under a key id; returns $this for chaining.
     */
    public function add(string $kid, Signer $signer): self
    {
        $this->signers[$kid] = $signer;
        return $this;
    }

    /**
     * Return the kid used when signing fresh tokens.
     */
    public function defaultKid(): string
    {
        return $this->defaultKid;
    }

    /**
     * Resolve a signer by kid, defaulting to the ring's default kid.
     */
    public function signer(?string $kid = null): Signer
    {
        $kid ??= $this->defaultKid;
        return $this->signers[$kid]
            ?? throw new \OutOfBoundsException("No signer registered for kid \"$kid\".");
    }
}
