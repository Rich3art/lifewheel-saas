<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

final class PermissionFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'name' => Str::title($name),
            'slug' => str_replace('-', '.', Str::slug($name)).'.'.fake()->unique()->numberBetween(1000, 9999),
            'description' => fake()->sentence(),
            'is_system' => false,
        ];
    }
}
