<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Log;

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

        // Route-model-binding failures (e.g. GET /assignments/999999) throw
        // ModelNotFoundException, but Handler::prepareException() converts
        // that to NotFoundHttpException — preserving the raw
        // "No query results for model [App\Models\X] {id}" message, which
        // leaks the internal model namespace — before any custom render()
        // callback for ModelNotFoundException itself ever gets a chance to
        // run. So this must target NotFoundHttpException and check its
        // wrapped previous exception instead. Returning null here lets any
        // other 404 (e.g. a route that doesn't exist at all) fall through
        // to Laravel's normal handling, untouched.
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            $previous = $e->getPrevious();

            if ($previous instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                $model = class_basename($previous->getModel());

                return response()->json(['message' => "{$model} not found."], 404);
            }

            return null;
        });

        // AuthorizationException (every $this->authorize() denial) is in
        // Laravel's internal "don't report" list by default, so a plain
        // report() callback for it would never fire — stopIgnoring() is
        // required to make it reportable before registering the callback.
        // Returning false from the callback stops it from also falling
        // through to the default logger, so each denial logs exactly once.
        $exceptions->stopIgnoring(\Illuminate\Auth\Access\AuthorizationException::class);

        $exceptions->report(function (\Illuminate\Auth\Access\AuthorizationException $e) {
            Log::warning('Authorization denied', [
                'user_id' => auth()->id(),
                'path' => request()->path(),
                'message' => $e->getMessage(),
            ]);

            return false;
        });
    })->create();
