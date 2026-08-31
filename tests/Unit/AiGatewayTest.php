<?php

namespace Tests\Unit;

use App\Models\AiModelRoute;
use App\Models\AiProvider;
use App\Models\Feature;
use App\Models\Package;
use App\Models\User;
use App\Services\AI\AiGateway;
use App\Services\AI\AiRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

final class AiGatewayTest extends TestCase
{
    use RefreshDatabase;

    public function test_mock_gateway_records_successful_usage(): void
    {
        $user = $this->entitledUser('ai.coach');
        $provider = AiProvider::query()->create(['key' => 'mock', 'name' => 'Mock', 'enabled' => true, 'mock_mode' => true]);
        AiModelRoute::query()->create(['feature_slug' => 'ai.coach', 'ai_provider_id' => $provider->id, 'model' => 'mock-coach-v1']);

        $response = app(AiGateway::class)->generate(new AiRequest(
            featureSlug: 'ai.coach',
            systemPrompt: 'You are a coach.',
            userPrompt: 'Help me plan.',
            user: $user,
        ));

        $this->assertSame('mock', $response->providerKey);
        $this->assertDatabaseHas('ai_usage_events', [
            'user_id' => $user->id,
            'feature_slug' => 'ai.coach',
            'status' => 'succeeded',
        ]);
    }

    public function test_gateway_denies_unentitled_user(): void
    {
        $this->expectException(RuntimeException::class);

        app(AiGateway::class)->generate(new AiRequest(
            featureSlug: 'ai.coach',
            systemPrompt: 'System',
            userPrompt: 'Prompt',
            user: User::factory()->create(),
        ));
    }

    public function test_gateway_enforces_route_monthly_limit(): void
    {
        $this->expectException(RuntimeException::class);

        $user = $this->entitledUser('ai.coach');
        $provider = AiProvider::query()->create(['key' => 'mock', 'name' => 'Mock', 'enabled' => true, 'mock_mode' => true]);
        AiModelRoute::query()->create(['feature_slug' => 'ai.coach', 'ai_provider_id' => $provider->id, 'model' => 'mock-coach-v1', 'monthly_limit' => 0]);

        app(AiGateway::class)->generate(new AiRequest(
            featureSlug: 'ai.coach',
            systemPrompt: 'System',
            userPrompt: 'Prompt',
            user: $user,
        ));
    }

    private function entitledUser(string $featureSlug): User
    {
        $user = User::factory()->create();
        $feature = Feature::query()->create(['name' => $featureSlug, 'slug' => $featureSlug]);
        $package = Package::factory()->create();
        $package->features()->attach([$feature->id => ['enabled' => true]]);
        $user->packages()->attach($package->id, ['status' => 'active', 'starts_at' => now()]);

        return $user;
    }
}
