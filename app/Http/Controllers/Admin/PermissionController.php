<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

final class PermissionController extends Controller
{
    public function index(): View
    {
        return view('admin.permissions.index', [
            'permissions' => Permission::query()->orderBy('name')->paginate(50),
        ]);
    }

    public function store(Request $request, AuditLogger $audit): RedirectResponse
    {
        $attributes = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:160', 'unique:permissions,slug'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $permission = Permission::query()->create([
            'name' => $attributes['name'],
            'slug' => $attributes['slug'] ?: Str::slug($attributes['name'], '.'),
            'description' => $attributes['description'] ?? null,
            'is_system' => false,
        ]);

        $audit->log('admin.permission_created', $request->user(), $permission);

        return back()->with('status', 'permission-created');
    }
}
