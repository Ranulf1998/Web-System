<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class TenantController extends Controller
{
    protected function seedTenantRoles(Tenant $tenant): Role
    {
        $permissions = [
            'use pos',
            'create orders',
            'process payments',
            'view products',
            'manage products',
            'view reports',
            'manage users',
            'delete users',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $ownerRole = Role::firstOrCreate([
            'name' => 'Owner',
            'guard_name' => 'web',
            'tenant_id' => $tenant->id,
        ]);
        $ownerRole->syncPermissions($permissions);

        $cashierRole = Role::firstOrCreate([
            'name' => 'Cashier',
            'guard_name' => 'web',
            'tenant_id' => $tenant->id,
        ]);
        $cashierRole->syncPermissions([
            'use pos',
            'create orders',
            'process payments',
            'view products',
        ]);

        return $ownerRole;
    }
    protected function addLocalSubdomainHost(string $subdomain): bool
    {
        if (!app()->environment('local')) {
            return true;
        }

        $domain = config('app.domain');

        if (!$domain || $domain === 'localhost') {
            return false;
        }

        $host = strtolower("{$subdomain}.{$domain}");
        $hostsPath = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'
            ? 'C:\\Windows\\System32\\drivers\\etc\\hosts'
            : '/etc/hosts';

        $content = @file_get_contents($hostsPath);

        if ($content === false) {
            Log::warning('Unable to read hosts file when creating tenant subdomain.', [
                'hosts_path' => $hostsPath,
                'host' => $host,
            ]);
            return false;
        }

        if (preg_match('/^\s*127\.0\.0\.1\s+' . preg_quote($host, '/') . '(\s|$)/mi', $content)) {
            return true;
        }

        $line = PHP_EOL . "127.0.0.1\t{$host}";
        $written = @file_put_contents($hostsPath, $line, FILE_APPEND | LOCK_EX);

        if ($written === false) {
            Log::warning('Unable to append tenant subdomain to hosts file. Try running server as administrator.', [
                'hosts_path' => $hostsPath,
                'host' => $host,
            ]);
            return false;
        }

        return true;
    }

    public function create(): View
    {
        return view('tenant.create');
    }

    public function shopLogin(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'subdomain' => 'required|alpha_dash|exists:tenants,subdomain',
        ]);

        return redirect()->to(route('login', ['subdomain' => $data['subdomain']]));
    }

    public function store(Request $request): ViewContract|RedirectResponse
    {
        $allowedPlans = implode(',', array_keys(config('plans')));

        $data = $request->validate([
            'shop_name' => 'required',
            'subdomain' => 'required|unique:tenants,subdomain|alpha_dash',
            'plan' => "required|in:{$allowedPlans}",
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|confirmed|min:8',
        ]);

        $tenant = DB::transaction(function () use ($data) {
            $tenant = Tenant::create([
                'name' => $data['shop_name'],
                'subdomain' => $data['subdomain'],
                'plan' => $data['plan'],
            ]);

            $user = User::create([
                'tenant_id' => $tenant->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);

            $ownerRole = $this->seedTenantRoles($tenant);
            $user->assignRole($ownerRole);

            return $tenant;
        });

        $tenantLoginUrl = route('login', ['subdomain' => $tenant->subdomain]);

        $hostReady = $this->addLocalSubdomainHost($tenant->subdomain);

        $payload = [
            'success' => 'Shop created successfully.',
            'tenant_login_url' => $tenantLoginUrl,
            'tenant_subdomain' => $tenant->subdomain,
        ];

        if (! $hostReady) {
            $payload['warning'] = 'Automatic redirect could not be completed locally. Click the login link below.';
        }

        return view('tenant.create', $payload);
    }
}
