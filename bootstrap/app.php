<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(function (Request $request) {
            $subdomain = $request->route('subdomain')
                ?? (app()->bound('tenant') ? app('tenant')->subdomain : null);

            return $subdomain
                ? route('login', ['subdomain' => $subdomain], false)
                : route('home', [], false);
        });

        $middleware->redirectUsersTo(function (Request $request) {
            $subdomain = $request->route('subdomain')
                ?? (app()->bound('tenant') ? app('tenant')->subdomain : null)
                ?? $request->user()?->tenant?->subdomain;

            return $subdomain
                ? route('dashboard', ['subdomain' => $subdomain], false)
                : route('home', [], false);
        });

        $middleware->alias([
            'tenant' => \App\Http\Middleware\IdentifyTenant::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
