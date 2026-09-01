<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RbacSeeder::class);
        $this->call(SaasSeeder::class);
        $this->call(CmsSeeder::class);
        $this->call(MemberSettingsSeeder::class);
        $this->call(AiSeeder::class);
        $this->call(BillingSeeder::class);
    }
}
