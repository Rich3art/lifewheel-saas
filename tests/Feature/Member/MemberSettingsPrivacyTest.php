<?php

namespace Tests\Feature\Member;

use App\Models\MemberSettingsSection;
use App\Models\PrivacyRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class MemberSettingsPrivacyTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_open_enabled_settings_sections(): void
    {
        $user = User::factory()->create();
        MemberSettingsSection::query()->create(['key' => 'profile', 'label' => 'Profile', 'enabled' => true, 'sort_order' => 10]);
        MemberSettingsSection::query()->create(['key' => 'ai', 'label' => 'AI preferences', 'enabled' => false, 'sort_order' => 20]);

        $this->actingAs($user)->get('/app/settings')
            ->assertOk()
            ->assertSee('Profile')
            ->assertDontSee('AI preferences');
    }

    public function test_member_can_create_data_export_request(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/app/privacy-requests', [
            'type' => 'data_export',
            'details' => 'I need a portable copy.',
        ])->assertRedirect();

        $this->assertDatabaseHas('privacy_requests', [
            'user_id' => $user->id,
            'type' => 'data_export',
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('data_exports', [
            'user_id' => $user->id,
            'status' => 'pending',
            'format' => 'json',
        ]);
    }

    public function test_member_settings_only_show_own_privacy_requests(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        MemberSettingsSection::query()->create(['key' => 'privacy', 'label' => 'Privacy', 'enabled' => true, 'sort_order' => 10]);
        PrivacyRequest::query()->create(['user_id' => $user->id, 'type' => 'correction', 'details' => 'Mine']);
        PrivacyRequest::query()->create(['user_id' => $other->id, 'type' => 'erasure', 'details' => 'Other user private request']);

        $this->actingAs($user)->get('/app/settings')
            ->assertOk()
            ->assertSee('Mine')
            ->assertDontSee('Other user private request');
    }
}
