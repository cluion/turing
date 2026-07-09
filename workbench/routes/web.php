<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Keep the PoW cheap so a real browser solves it well within the e2e timeout;
// the wire contract is unchanged, only the difficulty is lowered for the demo.
config([
    'turing.types.pow.cost' => 100,
    'turing.types.pow.maxcounter' => 500,
]);

// Serve the built browser widget from the core package's dist so the demo needs
// no static-file copying. Falls back to a clear error if the bundle is missing.
Route::get('/turing.global.js', function () {
    $bundle = dirname(__DIR__, 2) . '/js/packages/core/dist/turing.global.js';
    abort_unless(is_file($bundle), 404, 'Build the widget first: cd js/packages/core && pnpm build');

    return response()->file($bundle, ['Content-Type' => 'text/javascript']);
});

// Demo form page.
Route::get('/captcha-demo', fn () => view('demo'));

// Validates the captcha token; the whole point of the e2e.
Route::post('/submit', function (Request $request) {
    $request->validate(['turing_token' => 'required|turing']);

    return response()->json(['ok' => true]);
});
