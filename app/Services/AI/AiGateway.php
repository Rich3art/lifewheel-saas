<?php

namespace App\Services\AI;

use App\Models\AiModelRoute;
use App\Models\AiUsageEvent;
use App\Services\AI\Contracts\AiProviderClient;
use App\Services\EntitlementService;
use RuntimeException;

final readonly class AiGateway
{
    public function __construct(private EntitlementService $entitlements)
    {
    }

    public function generate(AiRequest $request): AiResponse
    {
        if ($request->user && ! $this->entitlements->userHasFeature($request->user, $request->featureSlug)) {
            throw new RuntimeException('User is not entitled to this AI feature.');
        }

        $route = $this->routeFor($request->featureSlug);

        if ($request->user) {
            $this->assertWithinLimit($request, $route);
        }

        try {
            $response = $this->clientFor($route)->generate($request, $route);
            $this->recordUsage($request, $response, 'succeeded');

            return $response;
        } catch (\Throwable $exception) {
            $this->recordUsage($request, new AiResponse('', [], $route->provider?->key ?? 'mock', $route->model), 'failed');

            throw $exception;
        }
    }

    public function routeFor(string $featureSlug): AiModelRoute
    {
        $route = AiModelRoute::query()
            ->with('provider')
            ->where('feature_slug', $featureSlug)
            ->where('enabled', true)
            ->orderBy('sort_order')
            ->first();

        if ($route) {
            return $route;
        }

        $mockProvider = \App\Models\AiProvider::query()->firstOrCreate(
            ['key' => 'mock'],
            ['name' => 'Mock', 'enabled' => true, 'mock_mode' => true],
        );

        return AiModelRoute::query()->create([
            'feature_slug' => $featureSlug,
            'ai_provider_id' => $mockProvider->id,
            'model' => config('ai.default_model', 'mock-coach-v1'),
            'enabled' => true,
            'sort_order' => 1000,
        ])->load('provider');
    }

    private function clientFor(AiModelRoute $route): AiProviderClient
    {
        $provider = $route->provider;

        if (! $provider || $provider->mock_mode || $provider->key === 'mock') {
            return app(MockAiProviderClient::class);
        }

        if (! $provider->enabled) {
            throw new RuntimeException('AI provider is disabled.');
        }

        return match ($provider->key) {
            'openai' => app(OpenAiProviderClient::class),
            'anthropic' => app(AnthropicProviderClient::class),
            default => throw new RuntimeException('Unsupported AI provider.'),
        };
    }

    private function assertWithinLimit(AiRequest $request, AiModelRoute $route): void
    {
        $configured = $route->monthly_limit;
        $packageLimit = $this->entitlements->limitFor($request->user, $request->featureSlug.'.monthly');
        $limit = $configured ?? ($packageLimit !== null ? (int) $packageLimit : null);

        if ($limit === null) {
            return;
        }

        $used = AiUsageEvent::query()
            ->whereBelongsTo($request->user)
            ->where('feature_slug', $request->featureSlug)
            ->where('status', 'succeeded')
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();

        if ($used >= $limit) {
            throw new RuntimeException('AI monthly usage limit reached.');
        }
    }

    private function recordUsage(AiRequest $request, AiResponse $response, string $status): void
    {
        AiUsageEvent::query()->create([
            'user_id' => $request->user?->id,
            'feature_slug' => $request->featureSlug,
            'provider_key' => $response->providerKey,
            'model' => $response->model,
            'input_tokens' => $response->inputTokens,
            'output_tokens' => $response->outputTokens,
            'estimated_cost_cents' => $response->estimatedCostCents,
            'status' => $status,
            'request_hash' => hash('sha256', $request->featureSlug.'|'.$request->systemPrompt.'|'.$request->userPrompt),
            'metadata' => $request->metadata,
        ]);
    }
}
