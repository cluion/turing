<?php
declare(strict_types=1);

namespace Cluion\Turing\Laravel\Blade;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\HtmlString;
use Illuminate\View\Component;

/**
 * Renders the <x-turing/> mount point: a data-attributed container the client
 * widget (delivered separately) reads to fetch and render the challenge. No
 * script is emitted here, keeping the component CSP-safe by construction.
 */
final class TuringComponent extends Component
{
    /**
     * @param string      $type  challenge type; empty means the config default
     * @param string|null $field hidden field name; null means the config value
     * @param string|null $url   explicit endpoint URL that overrides the route
     */
    public function __construct(
        public string $type = '',
        public ?string $field = null,
        public ?string $url = null,
    ) {
    }

    /**
     * Render the data-attributed container as raw HTML.
     */
    public function render(): Htmlable
    {
        $type = $this->type !== '' ? $this->type : (string) config('turing.default', 'math');
        $field = $this->field ?? (string) config('turing.field', 'turing_token');

        $html = sprintf(
            '<div data-turing data-turing-url="%s" data-turing-type="%s" data-turing-field="%s"></div>',
            $this->escape($this->resolveUrl()),
            $this->escape($type),
            $this->escape($field),
        );

        return new HtmlString($html);
    }

    /**
     * Resolve the endpoint URL: explicit attribute, then named route, then the
     * configured URI as a fallback when the route is disabled.
     */
    private function resolveUrl(): string
    {
        if ($this->url !== null) {
            return $this->url;
        }
        if (Route::has('turing.challenge')) {
            return route('turing.challenge');
        }
        return (string) config('turing.route.uri', '/turing/challenge');
    }

    /**
     * Escape a value for safe inclusion in a double-quoted HTML attribute.
     */
    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
