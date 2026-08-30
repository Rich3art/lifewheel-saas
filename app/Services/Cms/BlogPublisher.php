<?php

namespace App\Services\Cms;

use App\Models\BlogPost;
use App\Models\User;

final class BlogPublisher
{
    public function save(BlogPost $post, array $attributes, ?User $actor): BlogPost
    {
        $post->fill($attributes);
        $post->author_id ??= $actor?->id;
        $post->save();

        $post->revisions()->create([
            'title' => $post->title,
            'excerpt' => $post->excerpt,
            'body' => $post->body,
            'status' => $post->status,
            'seo_title' => $post->seo_title,
            'meta_description' => $post->meta_description,
            'canonical_url' => $post->canonical_url,
            'open_graph' => $post->open_graph,
            'created_by' => $actor?->id,
        ]);

        return $post->fresh(['author', 'categories', 'tags']);
    }
}
