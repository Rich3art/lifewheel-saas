<?php

namespace Tests\Feature\Admin;

use App\Models\DataExport;
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

    public function test_admin_processing_data_export_generates_ready_export(): void
    {
        $admin = $this->privacyAdmin();
        $member = User::factory()->create();
        $privacyRequest = PrivacyRequest::query()->create(['user_id' => $member->id, 'type' => 'data_export']);
        $export = DataExport::query()->create([
            'user_id' => $member->id,
            'privacy_request_id' => $privacyRequest->id,
            'status' => 'pending',
            'format' => 'json',
        ]);

        $this->actingAs($admin)->put("/admin/privacy-requests/{$privacyRequest->id}", [
            'status' => 'processing',
        ])->assertRedirect();

        $export->refresh();

        $this->assertSame('ready', $export->status);
        $this->assertNotNull($export->path);
    }

    public function test_admin_must_confirm_erasure_before_completion(): void
    {
        $admin = $this->privacyAdmin();
        $member = User::factory()->create(['email' => 'erase-me@example.test']);
        $privacyRequest = PrivacyRequest::query()->create(['user_id' => $member->id, 'type' => 'erasure']);

        $this->actingAs($admin)->put("/admin/privacy-requests/{$privacyRequest->id}", [
            'status' => 'completed',
        ])->assertStatus(422);

        $this->actingAs($admin)->put("/admin/privacy-requests/{$privacyRequest->id}", [
            'status' => 'completed',
            'confirm_erasure' => 1,
        ])->assertRedirect();

        $this->assertSame('erased-user-'.$member->id.'@example.invalid', $member->fresh()->email);
    }

    private function privacyAdmin(): User
    {
        $admin = User::factory()->create();

        foreach (['admin.dashboard.view', 'admin.privacy.manage'] as $slug) {
            $admin->directPermissions()->attach(Permission::factory()->create(['slug' => $slug]));
        }

        return $admin;
    }
}
