<?php

namespace App\Services\AI;

use App\Models\AiModelRoute;
use App\Services\AI\Contracts\AiProviderClient;

final class MockAiProviderClient implements AiProviderClient
{
    public function generate(AiRequest $request, AiModelRoute $route): AiResponse
    {
        $structured = $request->responseSchema ? $this->mockStructured($request->responseSchema) : [];

        return new AiResponse(
            content: $structured ? json_encode($structured) : 'Mock AI response generated for '.$request->featureSlug.'.',
            structured: $structured,
            providerKey: $route->provider?->key ?? 'mock',
            model: $route->model,
            inputTokens: str_word_count($request->systemPrompt.' '.$request->userPrompt),
            outputTokens: 12,
        );
    }

    private function mockStructured(array $schema): array
    {
        $properties = $schema['properties'] ?? [];
        $structured = [];

        foreach ($properties as $key => $definition) {
            $structured[$key] = ($definition['type'] ?? null) === 'array'
                ? ['Mock '.$key.' generated for local development.']
                : 'Mock '.$key.' generated for local development.';
        }

        return $structured;
    }
}
