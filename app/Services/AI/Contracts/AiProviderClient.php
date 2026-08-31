<?php

namespace App\Services\AI\Contracts;

use App\Models\AiModelRoute;
use App\Services\AI\AiRequest;
use App\Services\AI\AiResponse;

interface AiProviderClient
{
    public function generate(AiRequest $request, AiModelRoute $route): AiResponse;
}
