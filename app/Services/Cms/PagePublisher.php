<?php

namespace App\Services\Cms;

use App\Models\Page;
use App\Models\PageVersion;
use App\Models\User;

final class PagePublisher
{
    public function save(Page $page, array $attributes, ?User $actor): Page
    {
        $page->fill($attributes)->save();

        $versionNumber = ((int) $page->versions()->max('version')) + 1;
        $version = $page->versions()->create([
            'version' => $versionNumber,
            'title' => $page->title,
            'body' => $page->body,
            'seo_title' => $page->seo_title,
            'meta_description' => $page->meta_description,
            'canonical_url' => $page->canonical_url,
            'open_graph' => $page->open_graph,
            'status' => $page->status,
            'published_at' => $page->published_at,
            'created_by' => $actor?->id,
        ]);

        $page->forceFill(['current_version_id' => $version->id])->save();

        return $page->fresh(['currentVersion']);
    }

    public function publicVersion(Page $page): ?PageVersion
    {
        if (! $page->isPublished()) {
            return null;
        }

        return $page->currentVersion;
    }
}
