<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

final class PackageFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => Str::title($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1000, 9999),
            'short_description' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'active' => true,
            'public' => true,
            'featured' => false,
            'price_cents' => 1000,
            'currency' => 'USD',
            'billing_interval' => 'monthly',
            'trial_days' => 0,
            'sort_order' => 100,
        ];
    }
}
