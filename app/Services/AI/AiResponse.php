<?php

namespace App\Services\AI;

final readonly class AiResponse
{
    public function __construct(
        public string $content,
        public array $structured,
        public string $providerKey,
        public string $model,
        public int $inputTokens = 0,
        public int $outputTokens = 0,
        public int $estimatedCostCents = 0,
    ) {
    }
}
