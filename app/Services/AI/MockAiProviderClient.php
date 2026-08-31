<?php

namespace App\Services\AI;

use App\Models\AiModelRoute;
use App\Services\AI\Contracts\AiProviderClient;

final class MockAiProviderClient implements AiProviderClient
{
    public function generate(AiRequest $request, AiModelRoute $route): AiResponse
    {
        $structured = $request->responseSchema ? ['summary' => 'Mock AI response generated for local development.'] : [];

        return new AiResponse(
            content: 'Mock AI response generated for '.$request->featureSlug.'.',
            structured: $structured,
            providerKey: $route->provider?->key ?? 'mock',
            model: $route->model,
            inputTokens: str_word_count($request->systemPrompt.' '.$request->userPrompt),
            outputTokens: 12,
        );
    }
}
