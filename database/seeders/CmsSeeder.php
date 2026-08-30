<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Services\Cms\PagePublisher;
use Illuminate\Database\Seeder;

final class CmsSeeder extends Seeder
{
    public function run(): void
    {
        $publisher = app(PagePublisher::class);

        foreach ($this->pages() as $pageData) {
            if (Page::query()->where('slug', $pageData['slug'])->exists()) {
                continue;
            }

            $publisher->save(new Page(), $pageData, null);
        }
    }

    private function pages(): array
    {
        return [
            ['title' => 'About', 'slug' => 'about', 'status' => 'published', 'body' => 'About LifeWheel SaaS.', 'published_at' => now()],
            ['title' => 'Privacy Policy', 'slug' => 'privacy-policy', 'status' => 'published', 'body' => 'Privacy policy placeholder. Replace with reviewed policy text before production.', 'is_legal' => true, 'published_at' => now()],
            ['title' => 'Terms', 'slug' => 'terms', 'status' => 'published', 'body' => 'Terms placeholder. Replace with reviewed terms before production.', 'is_legal' => true, 'published_at' => now()],
            ['title' => 'Cookie Policy', 'slug' => 'cookie-policy', 'status' => 'published', 'body' => 'Cookie policy placeholder.', 'is_legal' => true, 'published_at' => now()],
            ['title' => 'Privacy Rights', 'slug' => 'privacy-rights', 'status' => 'published', 'body' => 'Privacy rights request information placeholder.', 'is_legal' => true, 'published_at' => now()],
            ['title' => 'Contact', 'slug' => 'contact', 'status' => 'published', 'body' => 'Contact information placeholder.', 'published_at' => now()],
        ];
    }
}
