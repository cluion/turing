<?php
declare(strict_types=1);

namespace Cluion\Turing\Laravel\Http;

use Cluion\Turing\Laravel\TuringManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Serves fresh challenges as JSON for the client widget to render.
 */
final class ChallengeController
{
    /**
     * Hold the manager used to issue challenges.
     */
    public function __construct(private readonly TuringManager $manager)
    {
    }

    /**
     * Issue a challenge of the requested type and return it as JSON.
     */
    public function show(Request $request): JsonResponse
    {
        $param = (string) config('turing.route.type_param', 'type');
        $type = $request->query($param);
        $challenge = $this->manager->challenge(is_string($type) && $type !== '' ? $type : null);

        return new JsonResponse([
            'token'   => $challenge->token,
            'image'   => $challenge->image,
            'params'  => $challenge->params,
            'type'    => $challenge->type,
            'expires' => $challenge->expires,
        ]);
    }
}
