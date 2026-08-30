<?php

namespace Tests\Feature\Admin;

use App\Models\MemberSettingsSection;
use App\Models\Permission;
use App\Models\PrivacyRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class MemberSettingsPrivacyAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_cannot_access_privacy_admin(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin/privacy-requests')->assertForbidden();
    }

    public function test_admin_can_update_member_settings_visibility(): void
    {
        $admin = User::factory()->create();
        $section = MemberSettingsSection::query()->create(['key' => 'billing', 'label' => 'Billing', 'enabled' => true]);

        foreach (['admin.dashboard.view', 'admin.member_settings.manage'] as $slug) {
            $admin->directPermissions()->attach(Permission::factory()->create(['slug' => $slug]));
        }

        $this->actingAs($admin)->put('/admin/member-settings', [
            'sections' => [
                $section->id => ['sort_order' => 25],
            ],
        ])->assertRedirect();

        $this->assertFalse($section->fresh()->enabled);
        $this->assertSame(25, $section->fresh()->sort_order);
    }

    public function test_admin_can_update_privacy_request_status(): void
    {
        $admin = User::factory()->create();
        $member = User::factory()->create();
        $privacyRequest = PrivacyRequest::query()->create(['user_id' => $member->id, 'type' => 'erasure']);

        foreach (['admin.dashboard.view', 'admin.privacy.manage'] as $slug) {
            $admin->directPermissions()->attach(Permission::factory()->create(['slug' => $slug]));
        }

        $this->actingAs($admin)->put("/admin/privacy-requests/{$privacyRequest->id}", [
            'status' => 'processing',
            'admin_notes' => 'Identity confirmation required.',
        ])->assertRedirect();

        $privacyRequest->refresh();

        $this->assertSame('processing', $privacyRequest->status);
        $this->assertSame($admin->id, $privacyRequest->processed_by);
    }
}
