<?php
declare(strict_types=1);

namespace Cluion\Turing\Laravel\Blade;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use Illuminate\View\Component;

/**
 * <x-turing/> component. Real container rendering is wired in the component
 * task; this stub emits a minimal container so the component is registered.
 */
final class TuringComponent extends Component
{
    /**
     * Placeholder render; replaced with the full container markup later.
     */
    public function render(): Htmlable
    {
        return new HtmlString('<div data-turing></div>');
    }
}
