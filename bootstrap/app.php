<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(prepend: [
            \App\Http\Middleware\ConfigureSessionByHost::class,
        ]);

        $getSubdomain = function (Request $request) {
            $host = $request->getHost();
            $mainDomain = config('app.domain');
            
            // If on main domain, no subdomain
            if ($host === $mainDomain || strpos($host, 'localhost') !== false) {
                return null;
            }
            
            $parts = explode('.', $host);
            $subdomain = $parts[0] ?? null;
            
            // If subdomain is 'www' or matches main domain, it's not really a subdomain
            if ($subdomain === 'www' || $subdomain === '') {
                return null;
            }
            
            return $subdomain;
        };

        $middleware->redirectGuestsTo(function (Request $request) use ($getSubdomain) {
            $subdomain = $getSubdomain($request);

            return $subdomain
                ? route('tenant.login', ['subdomain' => $subdomain], false)
                : route('home', [], false);
        });

        $middleware->redirectUsersTo(function (Request $request) use ($getSubdomain) {
            $user = $request->user();
            $subdomain = $getSubdomain($request);

            if ($subdomain) {
                if ($user?->tenant_id === null) {
                    return route('super-admin.dashboard', [], true);
                }

                return route('tenant.dashboard', ['subdomain' => $subdomain], false);
            }

            if ($user?->tenant_id === null) {
                return route('super-admin.dashboard', [], false);
            }

            return route('home', [], false);
        });

        $middleware->alias([
            'tenant' => \App\Http\Middleware\IdentifyTenant::class,
            'tenant.bandwidth' => \App\Http\Middleware\TrackTenantBandwidthUsage::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (PostTooLargeException $exception, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'File too large. Please upload a smaller file.',
                ], 413);
            }

            $previousUrl = url()->previous();
            $targetUrl = is_string($previousUrl) && $previousUrl !== '' ? $previousUrl : url('/');
            $separator = str_contains($targetUrl, '?') ? '&' : '?';

            return redirect()->to($targetUrl.$separator.'upload_error=file_too_large');
        });
    })->create();
