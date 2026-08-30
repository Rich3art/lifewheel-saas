<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Feature;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

final class FeatureController extends Controller
{
    public function index(): View
    {
        return view('admin.saas.features', [
            'features' => Feature::query()->orderBy('slug')->paginate(50),
        ]);
    }

    public function store(Request $request, AuditLogger $audit): RedirectResponse
    {
        $attributes = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:160', 'unique:features,slug'],
            'description' => ['nullable', 'string', 'max:1000'],
            'active' => ['nullable', 'boolean'],
        ]);

        $feature = Feature::query()->create([
            'name' => $attributes['name'],
            'slug' => $attributes['slug'] ?: str_replace('-', '.', Str::slug($attributes['name'])),
            'description' => $attributes['description'] ?? null,
            'active' => (bool) ($attributes['active'] ?? true),
            'source' => 'admin',
        ]);

        $audit->log('saas.feature_created', $request->user(), $feature);

        return back()->with('status', 'feature-created');
    }

    public function update(Request $request, Feature $feature, AuditLogger $audit): RedirectResponse
    {
        $attributes = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'active' => ['nullable', 'boolean'],
        ]);

        $feature->fill([
            'name' => $attributes['name'],
            'description' => $attributes['description'] ?? null,
            'active' => (bool) ($attributes['active'] ?? false),
        ])->save();

        $audit->log('saas.feature_updated', $request->user(), $feature);

        return back()->with('status', 'feature-updated');
    }
}
