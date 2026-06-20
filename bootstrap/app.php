<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetOrganizationFromUrl;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $trustedProxies = array_values(array_filter(array_map(
            static fn (string $proxy): string => trim($proxy),
            explode(',', (string) env('TRUSTED_PROXIES', ''))
        )));

        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->validateCsrfTokens(except: [
            'exams/*/*/save-answer',
            'logout',
        ]);

        // Trust proxies for HTTPS detection and real IP (including Cloudflare)
        $middleware->trustProxies(
            at: $trustedProxies ?: null,
            headers: \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR |
                \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST |
                \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT |
                \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO |
                \Illuminate\Http\Request::HEADER_X_FORWARDED_AWS_ELB
        );

        $middleware->web(append: [
            \App\Http\Middleware\TrustCloudflare::class, // Get real IP from Cloudflare
            HandleAppearance::class,
            SetOrganizationFromUrl::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (HttpExceptionInterface $exception, Request $request) {
            if (
                $exception->getStatusCode() === 409
                && $exception->getMessage() === 'This exam is already active in another browser or device.'
                && $request->is('exams/*/*/take')
            ) {
                return Inertia::render('errors/exam-session-conflict', [
                    'message' => $exception->getMessage(),
                ])->toResponse($request)->setStatusCode(409);
            }

            return null;
        });
    })->create();
