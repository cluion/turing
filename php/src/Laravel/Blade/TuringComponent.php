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
     * @param string      $type         challenge type; empty means the config default
     * @param string|null $field        hidden field name; null means the config value
     * @param string|null $url          explicit endpoint URL that overrides the route
     * @param bool        $autostart    when true, PoW solves immediately (no checkbox)
     * @param bool        $noWorker     when true, force main-thread PoW (disable Worker)
     * @param string|null $label        shorthand idle label ("I'm not a robot")
     * @param string|null $labelLoading loading-phase copy
     * @param string|null $labelIdle    idle checkbox copy (wins over $label)
     * @param string|null $labelSolving solving-phase copy
     * @param string|null $labelSolved  success copy
     * @param string|null $labelError   error copy
     * @param string|null $labelAria    checkbox aria-label
     */
    public function __construct(
        public string $type = '',
        public ?string $field = null,
        public ?string $url = null,
        public bool $autostart = false,
        public bool $noWorker = false,
        public ?string $label = null,
        public ?string $labelLoading = null,
        public ?string $labelIdle = null,
        public ?string $labelSolving = null,
        public ?string $labelSolved = null,
        public ?string $labelError = null,
        public ?string $labelAria = null,
        public ?string $labelRefresh = null,
        public bool $noRefresh = false,
    ) {
    }

    /**
     * Render the data-attributed container as raw HTML.
     */
    public function render(): Htmlable
    {
        $type = $this->type !== '' ? $this->type : (string) config('turing.default', 'pow');
        $field = $this->field ?? (string) config('turing.field', 'turing_token');

        $attrs = [
            'data-turing' => true,
            'data-turing-url' => $this->resolveUrl(),
            'data-turing-type' => $type,
            'data-turing-field' => $field,
        ];
        if ($this->autostart) {
            $attrs['data-turing-autostart'] = true;
        }
        if ($this->noWorker) {
            $attrs['data-turing-no-worker'] = true;
        }
        if ($this->noRefresh) {
            $attrs['data-turing-no-refresh'] = true;
        }

        $labelMap = [
            'data-turing-label' => $this->label,
            'data-turing-label-loading' => $this->labelLoading,
            'data-turing-label-idle' => $this->labelIdle,
            'data-turing-label-solving' => $this->labelSolving,
            'data-turing-label-solved' => $this->labelSolved,
            'data-turing-label-error' => $this->labelError,
            'data-turing-label-aria' => $this->labelAria,
            'data-turing-label-refresh' => $this->labelRefresh,
        ];
        foreach ($labelMap as $name => $value) {
            if ($value !== null && $value !== '') {
                $attrs[$name] = $value;
            }
        }

        $parts = [];
        foreach ($attrs as $name => $value) {
            if ($value === true) {
                $parts[] = $name;
            } else {
                $parts[] = sprintf('%s="%s"', $name, $this->escape((string) $value));
            }
        }

        return new HtmlString('<div ' . implode(' ', $parts) . '></div>');
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
