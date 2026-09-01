<?php

namespace Database\Seeders;

use App\Models\PaymentProvider;
use Illuminate\Database\Seeder;

final class BillingSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['key' => 'manual', 'name' => 'Manual', 'enabled' => true, 'sandbox' => true],
            ['key' => 'stripe', 'name' => 'Stripe', 'enabled' => false, 'sandbox' => true],
            ['key' => 'paypal', 'name' => 'PayPal', 'enabled' => false, 'sandbox' => true],
            ['key' => 'paystack', 'name' => 'Paystack', 'enabled' => false, 'sandbox' => true],
            ['key' => 'whop', 'name' => 'Whop', 'enabled' => false, 'sandbox' => true],
        ] as $provider) {
            PaymentProvider::query()->firstOrCreate(['key' => $provider['key']], $provider);
        }
    }
}
