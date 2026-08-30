<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class RoleController extends Controller
{
    public function index(): View
    {
        return view('admin.roles.index', [
            'roles' => Role::query()->withCount('users')->with('permissions')->orderBy('name')->get(),
            'permissions' => Permission::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, AuditLogger $audit): RedirectResponse
    {
        $attributes = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:160', 'unique:roles,slug'],
            'description' => ['nullable', 'string', 'max:1000'],
            'permissions' => ['array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        $slug = $attributes['slug'] ?? Str::slug($attributes['name']);

        $role = Role::query()->create([
            'name' => $attributes['name'],
            'slug' => $slug,
            'description' => $attributes['description'] ?? null,
            'is_system' => false,
        ]);

        $role->permissions()->sync($attributes['permissions'] ?? []);
        $audit->log('admin.role_created', $request->user(), $role);

        return back()->with('status', 'role-created');
    }

    public function update(Request $request, Role $role, AuditLogger $audit): RedirectResponse
    {
        $attributes = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:160', Rule::unique('roles')->ignore($role)],
            'description' => ['nullable', 'string', 'max:1000'],
            'permissions' => ['array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        $role->fill([
            'name' => $attributes['name'],
            'slug' => $role->is_system ? $role->slug : ($attributes['slug'] ?? $role->slug),
            'description' => $attributes['description'] ?? null,
        ])->save();

        $role->permissions()->sync($attributes['permissions'] ?? []);
        $audit->log('admin.role_updated', $request->user(), $role);

        return back()->with('status', 'role-updated');
    }
}
