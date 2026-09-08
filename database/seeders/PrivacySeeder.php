<?php

namespace Database\Seeders;

use App\Models\PrivacySetting;
use Illuminate\Database\Seeder;

final class PrivacySeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'export_expiry_days', 'value' => '7', 'description' => 'Number of days protected data exports remain downloadable.'],
            ['key' => 'privacy_request_due_days', 'value' => '30', 'description' => 'Default internal due window for privacy requests.'],
            ['key' => 'deletion_grace_days', 'value' => '14', 'description' => 'Recommended grace window before destructive erasure processing.'],
        ];

        foreach ($settings as $setting) {
            PrivacySetting::query()->updateOrCreate(['key' => $setting['key']], $setting);
        }
    }
}
