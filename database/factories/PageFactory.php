<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

final class PageFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->unique()->words(3, true);

        return [
            'title' => Str::title($title),
            'slug' => Str::slug($title),
            'status' => 'draft',
            'body' => fake()->paragraphs(3, true),
            'is_legal' => false,
        ];
    }
}
