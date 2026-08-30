<?php

namespace Database\Seeders;

use App\Models\MemberSettingsSection;
use Illuminate\Database\Seeder;

final class MemberSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $sections = [
            ['key' => 'profile', 'label' => 'Profile', 'description' => 'Name, username, avatar, timezone, and account identity.', 'required' => true, 'sort_order' => 10],
            ['key' => 'security', 'label' => 'Security', 'description' => 'Password, two-factor authentication, and account protection.', 'required' => true, 'sort_order' => 20],
            ['key' => 'privacy', 'label' => 'Privacy', 'description' => 'Privacy information, data export, correction, and erasure requests.', 'required' => true, 'sort_order' => 30],
            ['key' => 'billing', 'label' => 'Billing', 'description' => 'Current package and subscription status.', 'required' => false, 'sort_order' => 40],
            ['key' => 'notifications', 'label' => 'Notifications', 'description' => 'Future notification preferences registered by core or plugins.', 'required' => false, 'enabled' => false, 'sort_order' => 50],
            ['key' => 'community', 'label' => 'Community', 'description' => 'Future community profile, forum, and messaging preferences.', 'required' => false, 'enabled' => false, 'sort_order' => 60],
            ['key' => 'ai', 'label' => 'AI preferences', 'description' => 'Future AI model, coaching, and personalization controls.', 'required' => false, 'enabled' => false, 'sort_order' => 70],
        ];

        foreach ($sections as $section) {
            MemberSettingsSection::query()->updateOrCreate(
                ['key' => $section['key']],
                [
                    'label' => $section['label'],
                    'description' => $section['description'],
                    'source' => 'core',
                    'enabled' => $section['enabled'] ?? true,
                    'required' => $section['required'],
                    'sort_order' => $section['sort_order'],
                ],
            );
        }
    }
}
