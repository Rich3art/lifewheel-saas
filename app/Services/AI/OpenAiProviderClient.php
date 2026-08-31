<?php

namespace App\Services\AI;

use App\Models\AiModelRoute;
use App\Services\AI\Contracts\AiProviderClient;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class OpenAiProviderClient implements AiProviderClient
{
    public function generate(AiRequest $request, AiModelRoute $route): AiResponse
    {
        $provider = $route->provider;
        $apiKey = $provider?->encrypted_api_key;

        if (! $apiKey) {
            throw new RuntimeException('OpenAI API key is not configured.');
        }

        $payload = [
            'model' => $route->model,
            'messages' => [
                ['role' => 'system', 'content' => $request->systemPrompt],
                ['role' => 'user', 'content' => $request->userPrompt],
            ],
        ];

        if ($request->responseSchema) {
            $payload['response_format'] = [
                'type' => 'json_schema',
                'json_schema' => [
                    'name' => 'lifeos_response',
                    'strict' => true,
                    'schema' => $request->responseSchema,
                ],
            ];
        }

        $response = Http::timeout(config('ai.timeout_seconds', 30))
            ->withToken($apiKey)
            ->post(rtrim($provider->base_url ?: 'https://api.openai.com/v1', '/').'/chat/completions', $payload);

        if (! $response->successful()) {
            throw new RuntimeException('OpenAI request failed.');
        }

        $data = $response->json();
        $content = (string) data_get($data, 'choices.0.message.content', '');
        $structured = json_decode($content, true);

        return new AiResponse(
            content: $content,
            structured: is_array($structured) ? $structured : [],
            providerKey: 'openai',
            model: $route->model,
            inputTokens: (int) data_get($data, 'usage.prompt_tokens', 0),
            outputTokens: (int) data_get($data, 'usage.completion_tokens', 0),
        );
    }
}
