<?php
declare(strict_types=1);

namespace Cluion\Turing\Laravel;

use Cluion\Turing\Core\Challenge\Challenge;
use Cluion\Turing\Core\Challenge\MathType;
use Cluion\Turing\Core\Challenge\PowType;
use Cluion\Turing\Core\Challenge\TextType;
use Cluion\Turing\Core\Charset\DefaultCharset;
use Cluion\Turing\Core\Config;
use Cluion\Turing\Core\KeyRing;
use Cluion\Turing\Core\Store\NullStore;
use Cluion\Turing\Core\Store\Store;
use Cluion\Turing\Core\Token\HmacSigner;
use Cluion\Turing\Core\Turing;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Http\Request;

/**
 * Assembles a Core Turing instance from Laravel config: builds the key ring,
 * challenge types, single-use store, and clock, then caches the result. This
 * is the single place the framework layer touches Core wiring.
 */
final class TuringManager
{
    private ?Turing $turing = null;

    /**
     * @param array<string, mixed> $config the 'turing' config array
     */
    public function __construct(
        private readonly array $config,
        private readonly CacheRepository $cache,
    ) {
    }

    /**
     * Issue a fresh challenge of the given type (or the configured default).
     */
    public function challenge(?string $type = null): Challenge
    {
        return $this->make()->challenge($type);
    }

    /**
     * Verify a packed {t, a} token; true on success, false on a wrong answer.
     */
    public function verify(string $packed, ?string $answer = null): bool
    {
        return $this->make()->verify($packed, $answer);
    }

    /**
     * Verify the packed token carried in a request's configured field.
     */
    public function verifyRequest(Request $request, ?string $field = null): bool
    {
        $field ??= (string) ($this->config['field'] ?? 'turing_token');
        return $this->verify((string) $request->input($field, ''));
    }

    /**
     * Build (once) and return the Core Turing facade.
     */
    public function make(): Turing
    {
        return $this->turing ??= $this->build();
    }

    /**
     * Construct the Core Turing facade from config values.
     */
    private function build(): Turing
    {
        $secret = (string) ($this->config['secret'] ?? '');
        if ($secret === '') {
            throw new \RuntimeException('TURING_SECRET is not set; configure turing.secret.');
        }
        $pepper = (string) ($this->config['pepper'] ?? '');
        if ($pepper === '') {
            // Derive a stable pepper from the secret so a single TURING_SECRET works.
            $pepper = hash_hmac('sha256', 'turing-pepper', $secret);
        }

        $ring = (new KeyRing('default'))->add('default', new HmacSigner($secret));
        $charset = new DefaultCharset();
        $types = [
            'math' => new MathType(pepper: $pepper),
            'text' => new TextType(pepper: $pepper, charset: $charset),
            'pow'  => new PowType(),
        ];

        $config = new Config(
            defaultType: (string) ($this->config['default'] ?? 'math'),
            types: (array) ($this->config['types'] ?? []),
            now: static fn (): int => time(),
        );

        return new Turing($ring, $this->store(), $config, $types);
    }

    /**
     * Resolve the single-use store from config: cache-backed by default,
     * stateless when 'store' is 'null'.
     */
    private function store(): Store
    {
        return ($this->config['store'] ?? 'cache') === 'cache'
            ? new CacheStore($this->cache)
            : new NullStore();
    }
}
