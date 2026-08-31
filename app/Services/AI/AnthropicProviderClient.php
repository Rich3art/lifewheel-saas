<?php

namespace App\Services\AI;

use App\Models\AiModelRoute;
use App\Services\AI\Contracts\AiProviderClient;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class AnthropicProviderClient implements AiProviderClient
{
    public function generate(AiRequest $request, AiModelRoute $route): AiResponse
    {
        $provider = $route->provider;
        $apiKey = $provider?->encrypted_api_key;

        if (! $apiKey) {
            throw new RuntimeException('Anthropic API key is not configured.');
        }

        $response = Http::timeout(config('ai.timeout_seconds', 30))
            ->withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
            ])
            ->post(rtrim($provider->base_url ?: 'https://api.anthropic.com/v1', '/').'/messages', [
                'model' => $route->model,
                'max_tokens' => 1200,
                'system' => $request->systemPrompt,
                'messages' => [
                    ['role' => 'user', 'content' => $request->userPrompt],
                ],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Anthropic request failed.');
        }

        $data = $response->json();
        $content = (string) data_get($data, 'content.0.text', '');
        $structured = json_decode($content, true);

        return new AiResponse(
            content: $content,
            structured: is_array($structured) ? $structured : [],
            providerKey: 'anthropic',
            model: $route->model,
            inputTokens: (int) data_get($data, 'usage.input_tokens', 0),
            outputTokens: (int) data_get($data, 'usage.output_tokens', 0),
        );
    }
}
