<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Feature;
use App\Models\Package;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class UserController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));

        $users = User::query()
            ->with('roles')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'search' => $search,
        ]);
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', [
            'user' => $user->load('roles', 'directPermissions', 'packages', 'featureOverrides'),
            'roles' => Role::query()->orderBy('name')->get(),
            'permissions' => Permission::query()->orderBy('name')->get(),
            'packages' => Package::query()->where('active', true)->orderBy('sort_order')->get(),
            'features' => Feature::query()->where('active', true)->orderBy('slug')->get(),
        ]);
    }

    public function updatePermissions(Request $request, User $user, AuditLogger $audit): RedirectResponse
    {
        $permissionIds = $request->validate([
            'permissions' => ['array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ])['permissions'] ?? [];

        $user->directPermissions()->sync($permissionIds);
        $audit->log('admin.user_permissions_updated', $request->user(), $user, ['permission_ids' => $permissionIds]);

        return back()->with('status', 'user-permissions-updated');
    }

    public function updatePackages(Request $request, User $user, AuditLogger $audit): RedirectResponse
    {
        $attributes = $request->validate([
            'package_id' => ['nullable', 'integer', 'exists:packages,id'],
            'ends_at' => ['nullable', 'date'],
        ]);

        $user->packages()->detach();

        if ($attributes['package_id'] ?? null) {
            $user->packages()->attach($attributes['package_id'], [
                'status' => 'active',
                'starts_at' => now(),
                'ends_at' => $attributes['ends_at'] ?? null,
                'assigned_by' => $request->user()->id,
            ]);
        }

        $audit->log('admin.user_package_updated', $request->user(), $user, $attributes);

        return back()->with('status', 'user-package-updated');
    }

    public function updateFeatureOverrides(Request $request, User $user, AuditLogger $audit): RedirectResponse
    {
        $attributes = $request->validate([
            'overrides' => ['array'],
            'overrides.*' => ['in:grant,deny'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $user->featureOverrides()->delete();

        foreach (($attributes['overrides'] ?? []) as $featureId => $mode) {
            $user->featureOverrides()->create([
                'feature_id' => (int) $featureId,
                'enabled' => $mode === 'grant',
                'reason' => $attributes['reason'] ?? null,
                'created_by' => $request->user()->id,
            ]);
        }

        $audit->log('admin.user_feature_overrides_updated', $request->user(), $user, ['feature_ids' => array_keys($attributes['overrides'] ?? [])]);

        return back()->with('status', 'user-feature-overrides-updated');
    }

    public function updateRoles(Request $request, User $user, AuditLogger $audit): RedirectResponse
    {
        $roleIds = $request->validate([
            'roles' => ['array'],
            'roles.*' => ['integer', 'exists:roles,id'],
        ])['roles'] ?? [];

        $containsProtectedRole = Role::query()
            ->whereIn('id', $roleIds)
            ->where('is_protected', true)
            ->exists();

        abort_if($containsProtectedRole && ! $request->user()->hasPermission('admin.roles.manage'), 403);

        $user->roles()->sync($roleIds);
        $audit->log('admin.user_roles_updated', $request->user(), $user, ['role_ids' => $roleIds]);

        return back()->with('status', 'user-roles-updated');
    }

    public function suspend(Request $request, User $user, AuditLogger $audit): RedirectResponse
    {
        abort_if($request->user()->is($user), 422, 'You cannot suspend your own account.');

        $user->forceFill(['suspended_at' => now()])->save();
        $audit->log('admin.user_suspended', $request->user(), $user);

        return back()->with('status', 'user-suspended');
    }

    public function unsuspend(Request $request, User $user, AuditLogger $audit): RedirectResponse
    {
        $user->forceFill(['suspended_at' => null])->save();
        $audit->log('admin.user_unsuspended', $request->user(), $user);

        return back()->with('status', 'user-unsuspended');
    }
}
