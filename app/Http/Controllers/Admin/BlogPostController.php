<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Services\AuditLogger;
use App\Services\Cms\BlogPublisher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

final class BlogPostController extends Controller
{
    public function index(): View
    {
        return view('admin.cms.blog.index', [
            'posts' => BlogPost::query()->with('author')->latest()->paginate(25),
        ]);
    }

    public function create(): View
    {
        return $this->editView(new BlogPost());
    }

    public function store(Request $request, BlogPublisher $publisher, AuditLogger $audit): RedirectResponse
    {
        $post = $publisher->save(new BlogPost(), $this->validated($request), $request->user());
        $this->syncTaxonomy($post, $request);
        $audit->log('cms.blog_post_created', $request->user(), $post);

        return redirect()->route('admin.blog.edit', $post)->with('status', 'post-created');
    }

    public function edit(BlogPost $post): View
    {
        return $this->editView($post->load('categories', 'tags', 'revisions'));
    }

    public function update(Request $request, BlogPost $post, BlogPublisher $publisher, AuditLogger $audit): RedirectResponse
    {
        $publisher->save($post, $this->validated($request, $post), $request->user());
        $this->syncTaxonomy($post, $request);
        $audit->log('cms.blog_post_updated', $request->user(), $post);

        return back()->with('status', 'post-updated');
    }

    private function editView(BlogPost $post): View
    {
        if (! $post->exists) {
            $post->setRelation('categories', collect());
            $post->setRelation('tags', collect());
            $post->setRelation('revisions', collect());
        }

        return view('admin.cms.blog.edit', [
            'post' => $post,
            'categories' => BlogCategory::query()->orderBy('name')->get(),
            'tags' => BlogTag::query()->orderBy('name')->get(),
        ]);
    }

    private function validated(Request $request, ?BlogPost $post = null): array
    {
        $slugRule = $post?->exists ? 'unique:blog_posts,slug,'.$post->id : 'unique:blog_posts,slug';
        $attributes = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'slug' => ['nullable', 'string', 'max:200', $slugRule],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['nullable', 'string'],
            'status' => ['required', 'in:draft,published,scheduled,unpublished'],
            'featured_image_path' => ['nullable', 'string', 'max:255'],
            'seo_title' => ['nullable', 'string', 'max:160'],
            'meta_description' => ['nullable', 'string', 'max:320'],
            'canonical_url' => ['nullable', 'url', 'max:255'],
            'published_at' => ['nullable', 'date'],
        ]);

        $attributes['slug'] = $attributes['slug'] ?: Str::slug($attributes['title']);
        $attributes['published_at'] = $attributes['published_at'] ?? ($attributes['status'] === 'published' ? now() : null);

        return $attributes;
    }

    private function syncTaxonomy(BlogPost $post, Request $request): void
    {
        $post->categories()->sync($request->input('categories', []));
        $post->tags()->sync($request->input('tags', []));
    }
}
