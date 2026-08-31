<?php

namespace Database\Seeders;

use App\Models\Feature;
use App\Models\Package;
use Illuminate\Database\Seeder;

final class SaasSeeder extends Seeder
{
    public function run(): void
    {
        $features = collect([
            ['name' => 'LifeWheel', 'slug' => 'lifewheel.use'],
            ['name' => 'LifeWheel History', 'slug' => 'lifewheel.history'],
            ['name' => 'LifeWheel Analytics', 'slug' => 'lifewheel.analytics'],
            ['name' => 'Journal', 'slug' => 'journal.use'],
            ['name' => 'Journal Search', 'slug' => 'journal.search'],
            ['name' => 'Goals', 'slug' => 'goals.use'],
            ['name' => 'Goal Progress Tracking', 'slug' => 'goals.progress'],
            ['name' => 'Habits', 'slug' => 'habits.use'],
            ['name' => 'Projects', 'slug' => 'projects.use'],
            ['name' => 'Lessons', 'slug' => 'lessons.use'],
            ['name' => 'AI Analysis', 'slug' => 'ai.analysis'],
            ['name' => 'AI Coach', 'slug' => 'ai.coach'],
            ['name' => 'AI Reviews', 'slug' => 'ai.reviews'],
            ['name' => 'AI Goal Designer', 'slug' => 'ai.goal_designer'],
            ['name' => 'AI Habit Designer', 'slug' => 'ai.habit_designer'],
            ['name' => 'Forum', 'slug' => 'forum.use'],
            ['name' => 'Gamification', 'slug' => 'gamification.use'],
        ])->mapWithKeys(fn (array $feature): array => [
            $feature['slug'] => Feature::query()->firstOrCreate(
                ['slug' => $feature['slug']],
                ['name' => $feature['name'], 'description' => 'Default platform feature.', 'source' => 'core'],
            ),
        ]);

        $free = Package::query()->firstOrCreate(['slug' => 'free'], [
            'name' => 'Free',
            'short_description' => 'Static non-AI personal operating system tools.',
            'description' => 'Default editable free package.',
            'price_cents' => 0,
            'currency' => 'USD',
            'billing_interval' => 'monthly',
            'sort_order' => 10,
            'cta_label' => 'Start free',
            'landing_page_slug' => 'free',
        ]);

        $lessons = Package::query()->firstOrCreate(['slug' => 'lessons'], [
            'name' => 'Lessons',
            'short_description' => 'Free tools plus the Lessons Ledger.',
            'description' => 'Default editable lessons package.',
            'price_cents' => 1900,
            'currency' => 'USD',
            'billing_interval' => 'monthly',
            'sort_order' => 20,
            'cta_label' => 'Choose Lessons',
            'landing_page_slug' => 'lessons',
        ]);

        $premium = Package::query()->firstOrCreate(['slug' => 'premium-ai'], [
            'name' => 'Premium AI',
            'short_description' => 'AI analysis, coach, reviews, lessons, and static tools.',
            'description' => 'Default editable premium AI package.',
            'price_cents' => 4900,
            'currency' => 'USD',
            'billing_interval' => 'monthly',
            'sort_order' => 30,
            'cta_label' => 'Choose Premium',
            'landing_page_slug' => 'premium',
            'featured' => true,
        ]);

        $free->features()->syncWithoutDetaching($features->only([
            'lifewheel.use', 'lifewheel.history', 'journal.use', 'journal.search', 'goals.use', 'goals.progress', 'habits.use', 'projects.use',
            'forum.use', 'gamification.use',
        ])->pluck('id')->mapWithKeys(fn ($id): array => [$id => ['enabled' => true]])->all());

        $lessons->features()->syncWithoutDetaching($features->only([
            'lifewheel.use', 'lifewheel.history', 'journal.use', 'journal.search', 'goals.use', 'goals.progress', 'habits.use', 'projects.use',
            'lessons.use', 'forum.use', 'gamification.use',
        ])->pluck('id')->mapWithKeys(fn ($id): array => [$id => ['enabled' => true]])->all());

        $premium->features()->syncWithoutDetaching($features->pluck('id')->mapWithKeys(fn ($id): array => [$id => ['enabled' => true]])->all());

        $premium->limits()->updateOrCreate(['key' => 'ai.coach.messages.monthly'], ['value' => '250']);
        $premium->limits()->updateOrCreate(['key' => 'ai.reviews.monthly'], ['value' => '20']);
        $lessons->limits()->updateOrCreate(['key' => 'lesson.generations.monthly'], ['value' => '25']);
    }
}
