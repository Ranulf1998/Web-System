<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ConfigureSessionByHost
{
    public function handle(Request $request, Closure $next): Response
    {
        $host = Str::lower($request->getHost());

        // Host-only cookies prevent cross-subdomain session sharing.
        config([
            'session.domain' => null,
            'session.path' => '/',
            'session.cookie' => 'brewcloud-session-'.Str::slug($host),
        ]);

        return $next($request);
    }
}
