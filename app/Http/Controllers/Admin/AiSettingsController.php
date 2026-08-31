<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiModelRoute;
use App\Models\AiProvider;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class AiSettingsController extends Controller
{
    public function index(): View
    {
        return view('admin.ai.index', [
            'providers' => AiProvider::query()->orderBy('name')->get(),
            'routes' => AiModelRoute::query()->with('provider')->orderBy('feature_slug')->orderBy('sort_order')->get(),
        ]);
    }

    public function updateProvider(Request $request, AiProvider $provider, AuditLogger $audit): RedirectResponse
    {
        $attributes = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'enabled' => ['nullable', 'boolean'],
            'mock_mode' => ['nullable', 'boolean'],
            'base_url' => ['nullable', 'url', 'max:255'],
            'api_key' => ['nullable', 'string', 'max:4000'],
        ]);

        $data = [
            'name' => $attributes['name'],
            'enabled' => (bool) ($attributes['enabled'] ?? false),
            'mock_mode' => (bool) ($attributes['mock_mode'] ?? false),
            'base_url' => $attributes['base_url'] ?? null,
        ];

        if (($attributes['api_key'] ?? '') !== '') {
            $data['encrypted_api_key'] = $attributes['api_key'];
        }

        $provider->forceFill($data)->save();
        $audit->log('admin.ai_provider_updated', $request->user(), $provider, ['provider' => $provider->key]);

        return back()->with('status', 'ai-provider-updated');
    }

    public function updateRoute(Request $request, AiModelRoute $route, AuditLogger $audit): RedirectResponse
    {
        $attributes = $request->validate([
            'ai_provider_id' => ['nullable', 'integer', 'exists:ai_providers,id'],
            'model' => ['required', 'string', 'max:120'],
            'enabled' => ['nullable', 'boolean'],
            'monthly_limit' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:10000'],
        ]);

        $route->forceFill([
            'ai_provider_id' => $attributes['ai_provider_id'] ?? null,
            'model' => $attributes['model'],
            'enabled' => (bool) ($attributes['enabled'] ?? false),
            'monthly_limit' => $attributes['monthly_limit'] ?? null,
            'sort_order' => $attributes['sort_order'],
        ])->save();

        $audit->log('admin.ai_route_updated', $request->user(), $route, ['feature_slug' => $route->feature_slug]);

        return back()->with('status', 'ai-route-updated');
    }
}
