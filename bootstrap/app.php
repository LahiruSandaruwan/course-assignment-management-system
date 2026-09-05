<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // This is an API-only app with no named "login" web route.
        // ApplicationBuilder registers a default redirectGuestsTo(fn () =>
        // route('login')) unconditionally, which throws RouteNotFoundException
        // for any unauthenticated request that doesn't send
        // Accept: application/json. Every real client here is JSON-only, so
        // there is never anywhere to redirect a guest to.
        $middleware->redirectGuestsTo(fn () => null);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Belt-and-suspenders: the framework's default unauthenticated()
        // handler falls back to route('login') too when a request doesn't
        // expect JSON, so render AuthenticationException ourselves.
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        });
    })->create();
