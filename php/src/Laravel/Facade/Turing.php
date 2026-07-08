<?php
declare(strict_types=1);

namespace Cluion\Turing\Laravel\Facade;

use Cluion\Turing\Laravel\TuringManager;
use Illuminate\Support\Facades\Facade;

/**
 * Static proxy to TuringManager.
 *
 * @method static \Cluion\Turing\Core\Challenge\Challenge challenge(?string $type = null)
 * @method static bool verify(string $packed, ?string $answer = null)
 * @method static bool verifyRequest(\Illuminate\Http\Request $request, ?string $field = null)
 */
final class Turing extends Facade
{
    /**
     * Bind the facade to the manager singleton in the container.
     */
    protected static function getFacadeAccessor(): string
    {
        return TuringManager::class;
    }
}
