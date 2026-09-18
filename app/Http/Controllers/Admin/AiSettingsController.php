<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiModelRoute;
use App\Models\AiPromptSetting;
use App\Models\AiProvider;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class AiSettingsController extends Controller
{
    public function index(): View
    {
        $providers = AiProvider::query()->orderBy('name')->get();
        $routes = AiModelRoute::query()->with('provider')->orderBy('feature_slug')->orderBy('sort_order')->get();
        $openAiProvider = $providers->firstWhere('key', 'openai');
        $coachRoute = $routes->firstWhere('feature_slug', 'ai.coach');

        return view('admin.ai.index', [
            'providers' => $providers,
            'routes' => $routes,
            'openAiProvider' => $openAiProvider,
            'coachRoute' => $coachRoute,
            'lifeWheelAiReady' => (bool) (
                $openAiProvider?->enabled
                && ! $openAiProvider?->mock_mode
                && $openAiProvider?->encrypted_api_key
                && $coachRoute?->enabled
                && $coachRoute?->provider?->key === 'openai'
            ),
            'lifeWheelPrompt' => AiPromptSetting::query()
                ->where('key', 'lifewheel.feedback.system_prompt')
                ->first(),
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

    public function updatePrompt(Request $request, AuditLogger $audit): RedirectResponse
    {
        $attributes = $request->validate([
            'key' => ['required', 'string', 'in:lifewheel.feedback.system_prompt'],
            'label' => ['required', 'string', 'max:120'],
            'prompt' => ['required', 'string', 'min:20', 'max:12000'],
        ]);

        $prompt = AiPromptSetting::query()->updateOrCreate(
            ['key' => $attributes['key']],
            [
                'label' => $attributes['label'],
                'prompt' => $attributes['prompt'],
            ],
        );

        $audit->log('admin.ai_prompt_updated', $request->user(), $prompt, ['key' => $prompt->key]);

        return back()->with('status', 'ai-prompt-updated');
    }
}
