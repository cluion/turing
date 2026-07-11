<?php
declare(strict_types=1);

namespace Cluion\Turing\Tests\Laravel;

use Cluion\Turing\Laravel\Blade\TuringComponent;

final class TuringComponentTest extends TestCase
{
    /**
     * The component renders a data-turing container with the resolved URL,
     * type, and field.
     */
    public function test_renders_container_with_data_attributes(): void
    {
        $html = (new TuringComponent(type: 'pow'))->render()->toHtml();
        self::assertStringContainsString('data-turing', $html);
        self::assertStringContainsString('data-turing-type="pow"', $html);
        self::assertStringContainsString('data-turing-field="turing_token"', $html);
        self::assertStringContainsString('data-turing-url="', $html);
        self::assertStringContainsString('/turing/challenge', $html);
        self::assertStringNotContainsString('data-turing-autostart', $html);
    }

    /**
     * An explicit url attribute overrides the named route.
     */
    public function test_explicit_url_overrides_route(): void
    {
        $html = (new TuringComponent(type: 'math', url: '/custom/endpoint'))->render()->toHtml();
        self::assertStringContainsString('data-turing-url="/custom/endpoint"', $html);
    }

    /**
     * With no type, the component falls back to the configured default (pow).
     */
    public function test_defaults_type_from_config(): void
    {
        $html = (new TuringComponent())->render()->toHtml();
        self::assertStringContainsString('data-turing-type="pow"', $html);
    }

    /**
     * Optional PoW UX flags map to data attributes.
     */
    public function test_autostart_and_no_worker_flags(): void
    {
        $html = (new TuringComponent(type: 'pow', autostart: true, noWorker: true))->render()->toHtml();
        self::assertStringContainsString('data-turing-autostart', $html);
        self::assertStringContainsString('data-turing-no-worker', $html);
    }
}
