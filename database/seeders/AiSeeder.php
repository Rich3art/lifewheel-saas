<?php

namespace Database\Seeders;

use App\Models\AiModelRoute;
use App\Models\AiProvider;
use Illuminate\Database\Seeder;

final class AiSeeder extends Seeder
{
    public function run(): void
    {
        $mock = AiProvider::query()->updateOrCreate(
            ['key' => 'mock'],
            ['name' => 'Mock', 'enabled' => true, 'mock_mode' => true],
        );

        AiProvider::query()->firstOrCreate(
            ['key' => 'openai'],
            ['name' => 'OpenAI', 'enabled' => false, 'mock_mode' => true, 'base_url' => 'https://api.openai.com/v1'],
        );

        AiProvider::query()->firstOrCreate(
            ['key' => 'anthropic'],
            ['name' => 'Anthropic', 'enabled' => false, 'mock_mode' => true, 'base_url' => 'https://api.anthropic.com/v1'],
        );

        foreach (['ai.analysis', 'ai.coach', 'ai.reviews', 'ai.goal_designer', 'ai.habit_designer'] as $index => $feature) {
            AiModelRoute::query()->updateOrCreate(
                ['feature_slug' => $feature, 'sort_order' => 10],
                [
                    'ai_provider_id' => $mock->id,
                    'model' => config('ai.default_model', 'mock-coach-v1'),
                    'enabled' => true,
                    'monthly_limit' => null,
                ],
            );
        }
    }
}
