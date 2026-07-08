<?php
declare(strict_types=1);

namespace Cluion\Turing\Tests\Laravel;

final class ChallengeEndpointTest extends TestCase
{
    /**
     * GET the endpoint returns a JSON challenge with the expected shape.
     */
    public function test_get_challenge_returns_json(): void
    {
        $this->getJson('/turing/challenge?type=math')
            ->assertOk()
            ->assertJsonStructure(['token', 'image', 'params', 'type', 'expires'])
            ->assertJsonPath('type', 'math');
    }

    /**
     * Requesting the pow type returns params and a null image.
     */
    public function test_get_pow_challenge_returns_params(): void
    {
        $response = $this->getJson('/turing/challenge?type=pow')->assertOk();
        $response->assertJsonPath('type', 'pow');
        self::assertNull($response->json('image'));
        self::assertArrayHasKey('algorithm', $response->json('params'));
    }
}
