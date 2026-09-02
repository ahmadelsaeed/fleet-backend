<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
//            Route::middleware(['web', 'admin'])
//                ->prefix('admin')
//                ->as('admin.')
//                ->namespace('App\Http\Controllers\Admin')
//                ->group(base_path('routes/admin.php'));

            Route::middleware('api')
                ->prefix('api/v1')
                ->namespace('App\Http\Controllers\API')
                ->group(base_path('routes/api.php'));
        }
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
//    ->withExceptions(function (Exceptions $exceptions): void {
//        $exceptions->shouldRenderJsonWhen(
//            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
//        );
//    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Illuminate\Validation\ValidationException $exception) {
            if (request()->wantsJson()) {
                $firstError = collect($exception->errors())->flatten()->first();

                return response()->json([
                    'status' => 422,
                    'errors' => $firstError,
                ], 422);
            }
        });
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            if ($exception->getStatusCode() == 400 && request()->wantsJson()) {
                return response()->json(['status' => 400, 'errors' => __('Bad Request')], 400);
            }

            if ($exception->getStatusCode() == 403 && request()->wantsJson()) {
                return response()->json(['status' => 403, 'errors' => __('Forbidden')], 403);
            }

            if ($exception->getStatusCode() == 401 && request()->wantsJson()) {
                return response()->json(['status' => 401, 'errors' => __('Unauthorized')], 401);
            }

            if ($exception->getStatusCode() == 422 && request()->wantsJson()) {
                return response()->json(['status' => 422, 'errors' => __('Unauthorized')], 422);
            }

            if ($exception->getStatusCode() == 404 && request()->wantsJson()) {
                return response()->json(['status' => 404, 'errors' => __('Not Found')], 404);
            }

            if ($exception->getStatusCode() == 500 && request()->wantsJson()) {
                return response()->json(['status' => 500, 'errors' => __('Internal Server Error')], 500);
            }

            if ($exception->getStatusCode() == 503 && request()->wantsJson()) {
                return response()->json(['status' => 503, 'errors' => __('Service Unavailable')], 503);
            }
        });
    })
    ->create();
