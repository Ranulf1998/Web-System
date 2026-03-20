<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        ActivityLogger::log(
            'auth.login',
            'Logged in to tenant workspace',
            $request->user()
        );

        $subdomain = $request->route('subdomain')
            ?? (app()->bound('tenant') ? app('tenant')->subdomain : null);

        return $subdomain
            ? redirect()->route('tenant.dashboard', ['subdomain' => $subdomain])
            : redirect('/dashboard');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        ActivityLogger::log(
            'auth.logout',
            'Logged out from tenant workspace',
            $request->user()
        );

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        $subdomain = $request->route('subdomain')
            ?? (app()->bound('tenant') ? app('tenant')->subdomain : null);

        return $subdomain
            ? redirect()->route('tenant.login', ['subdomain' => $subdomain])
            : redirect()->route('home');
    }
}
