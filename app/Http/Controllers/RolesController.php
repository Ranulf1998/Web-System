<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;

class RolesController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:manage users');
    }

    protected function resolveRole(string $role): Role
    {
        $query = Role::where('tenant_id', tenant()->id);

        if (ctype_digit($role)) {
            $query->where('id', $role);
        } else {
            $query->where('name', $role);
        }

        return $query->firstOrFail();
    }

    public function index(): View
    {
        $roles = Role::with('permissions')->orderBy('name')->get();

        return view('roles.index', compact('roles'));
    }

    public function create(): View
    {
        $permissions = Permission::orderBy('name')->get();

        return view('roles.create', compact('permissions'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')->where('tenant_id', tenant()->id),
            ],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string'],
        ]);

        $role = Role::create([
            'name' => $data['name'],
            'guard_name' => 'web',
            'tenant_id' => tenant()->id,
        ]);

        $role->syncPermissions($data['permissions'] ?? []);

        return redirect()->route('roles.index', ['subdomain' => request()->route('subdomain')])
            ->with('status', 'Role created');
    }

    public function edit(string $subdomain, string $role): View
    {
        $role = $this->resolveRole($role);
        $permissions = Permission::orderBy('name')->get();
        $rolePermissions = $role->permissions->pluck('name')->toArray();

        return view('roles.edit', compact('role', 'permissions', 'rolePermissions'));
    }

    public function update(Request $request, string $subdomain, string $role): RedirectResponse
    {
        $role = $this->resolveRole($role);
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')->where('tenant_id', tenant()->id)->ignore($role->id),
            ],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string'],
        ]);

        $role->update([
            'name' => $data['name'],
        ]);

        $role->syncPermissions($data['permissions'] ?? []);

        return redirect()->route('roles.index', ['subdomain' => request()->route('subdomain')])
            ->with('status', 'Role updated');
    }

    public function destroy(string $subdomain, string $role): RedirectResponse
    {
        $role = $this->resolveRole($role);
        $role->delete();

        return redirect()->route('roles.index', ['subdomain' => request()->route('subdomain')])
            ->with('status', 'Role deleted');
    }
}
