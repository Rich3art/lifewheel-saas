<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use App\Models\BlogTag;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

final class BlogTaxonomyController extends Controller
{
    public function index(): View
    {
        return view('admin.cms.blog.taxonomy', [
            'categories' => BlogCategory::query()->orderBy('name')->get(),
            'tags' => BlogTag::query()->orderBy('name')->get(),
        ]);
    }

    public function storeCategory(Request $request, AuditLogger $audit): RedirectResponse
    {
        $attributes = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:160', 'unique:blog_categories,slug'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $category = BlogCategory::query()->create([
            'name' => $attributes['name'],
            'slug' => $attributes['slug'] ?: Str::slug($attributes['name']),
            'description' => $attributes['description'] ?? null,
        ]);

        $audit->log('cms.blog_category_created', $request->user(), $category);

        return back()->with('status', 'category-created');
    }

    public function storeTag(Request $request, AuditLogger $audit): RedirectResponse
    {
        $attributes = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:160', 'unique:blog_tags,slug'],
        ]);

        $tag = BlogTag::query()->create([
            'name' => $attributes['name'],
            'slug' => $attributes['slug'] ?: Str::slug($attributes['name']),
        ]);

        $audit->log('cms.blog_tag_created', $request->user(), $tag);

        return back()->with('status', 'tag-created');
    }
}
