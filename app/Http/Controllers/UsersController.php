<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use App\Support\ActivityLogger;
use App\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class UsersController extends Controller
{
    protected function ensureTenantRoles(): void
    {
        $permissions = [
            'use pos',
            'create orders',
            'process payments',
            'manage brewing orders',
            'view products',
            'view brewing guides',
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
            'tenant_id' => tenant()->id,
        ]);
        $ownerRole->syncPermissions($permissions);

        $cashierRole = Role::firstOrCreate([
            'name' => 'Cashier',
            'guard_name' => 'web',
            'tenant_id' => tenant()->id,
        ]);
        $cashierRole->syncPermissions([
            'use pos',
            'create orders',
            'process payments',
            'view products',
            'view brewing guides',
        ]);

        $baristaRole = Role::firstOrCreate([
            'name' => 'Barista',
            'guard_name' => 'web',
            'tenant_id' => tenant()->id,
        ]);
        $baristaRole->syncPermissions([
            'manage brewing orders',
            'view brewing guides',
        ]);
    }

    public function __construct()
    {
        $this->middleware('permission:manage users');
        $this->middleware('permission:delete users')->only(['destroy']);
        
        $this->middleware(function ($request, $next) {
            $planKey = tenant()->planKey();
            $maxUsers = config('plans.' . $planKey . '.max_users');
            abort_unless($maxUsers === null || $maxUsers > 1, 403, 'Staff management is not available on your current plan.');
            return $next($request);
        });
    }

    public function index(): View
    {
        $users = User::with('roles')->get();

        return view('users.index', compact('users'));
    }

    public function create(): View
    {
        $this->ensureTenantRoles();
        $roles = Role::orderBy('name')->get();

        return view('users.create', compact('roles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureTenantRoles();

        if (tenant()->planKey() === 'standard') {
            $staffCount = User::whereDoesntHave('roles', function ($query) {
                $query->where('name', 'Owner');
            })->count();

            if ($staffCount >= 3) {
                return back()->withErrors(['limit' => 'Standard plan allows up to 3 staff accounts.']);
            }
        }

        $planKey = tenant()->planKey();
        $maxUsers = config('plans.' . $planKey . '.max_users');
        if ($planKey !== 'standard' && $maxUsers !== null && User::count() >= $maxUsers) {
            return back()->withErrors(['limit' => 'User limit reached for your plan']);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:tenant.users,email'],
            'password' => ['required', 'confirmed', 'min:8'],
            'role' => ['required', 'string'],
        ]);

        $role = Role::where('name', $data['role'])->firstOrFail();

        $user = User::create([
            'tenant_id' => tenant()->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $user->assignRole($role);

        ActivityLogger::log(
            'user.created',
            'Created staff account for ' . $user->name,
            $user,
            ['role' => $role->name]
        );

        return redirect()->route('users.index', ['subdomain' => request()->route('subdomain')])
            ->with('status', 'User created');
    }

    public function edit(string $subdomain, string $user): View
    {
        $this->ensureTenantRoles();
        $user = User::findOrFail($user);
        $roles = Role::orderBy('name')->get();
        $permissions = Permission::orderBy('name')->get();
        $directPermissions = $user->getDirectPermissions()->pluck('name')->toArray();

        return view('users.edit', compact('user', 'roles', 'permissions', 'directPermissions'));
    }

    public function update(Request $request, string $subdomain, string $user): RedirectResponse
    {
        $user = User::findOrFail($user);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('tenant.users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'confirmed', 'min:8'],
            'role' => ['required', 'string'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string'],
        ]);

        $user->name = $data['name'];
        $user->email = $data['email'];

        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        $role = Role::where('name', $data['role'])->firstOrFail();
        $user->syncRoles([$role]);
        $user->syncPermissions($data['permissions'] ?? []);

        ActivityLogger::log(
            'user.updated',
            'Updated staff account for ' . $user->name,
            $user,
            ['role' => $role->name]
        );

        return redirect()->route('users.index', ['subdomain' => request()->route('subdomain')])
            ->with('status', 'User updated');
    }

    public function destroy(string $subdomain, string $user): RedirectResponse
    {
        $user = User::findOrFail($user);
        $userName = $user->name;
        $user->delete();

        ActivityLogger::log(
            'user.deleted',
            'Deleted staff account for ' . $userName,
            null,
            ['name' => $userName]
        );

        return redirect()->route('users.index', ['subdomain' => request()->route('subdomain')])
            ->with('status', 'User deleted');
    }
}
