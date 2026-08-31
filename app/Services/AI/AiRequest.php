<?php

namespace App\Services\AI;

use App\Models\User;

final readonly class AiRequest
{
    public function __construct(
        public string $featureSlug,
        public string $systemPrompt,
        public string $userPrompt,
        public ?array $responseSchema = null,
        public ?User $user = null,
        public array $metadata = [],
    ) {
    }
}
