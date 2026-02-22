<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SuperAdminController extends Controller
{
    public function showLoginForm(Request $request): View|RedirectResponse
    {
        if (Auth::check()) {
            if ($request->user()?->tenant_id === null) {
                return redirect()->route('super-admin.dashboard');
            }

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('super-admin.login')
                ->with('status', 'Tenant session ended. Please sign in as super admin.');
        }

        return view('super-admin.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        $request->session()->regenerate();

        if ($request->user()?->tenant_id !== null) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'This login is only for BrewCloud super-admin accounts.',
            ]);
        }

        return redirect()->intended(route('super-admin.dashboard'));
    }

    public function dashboard(): View
    {
        abort_unless(auth()->check() && auth()->user()->tenant_id === null, 403);

        $stats = [
            'tenants' => Tenant::count(),
            'tenant_users' => User::whereNotNull('tenant_id')->count(),
            'super_admins' => User::whereNull('tenant_id')->count(),
            'orders' => Order::count(),
            'products' => Product::count(),
            'sales_total' => (float) Order::sum('total'),
        ];

        $recentTenants = Tenant::query()
            ->latest()
            ->take(8)
            ->get(['name', 'subdomain', 'plan', 'created_at']);

        return view('super-admin.dashboard', [
            'stats' => $stats,
            'recentTenants' => $recentTenants,
        ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('super-admin.login');
    }
}
