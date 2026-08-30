<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Services\AuditLogger;
use App\Services\Cms\PagePublisher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

final class PageController extends Controller
{
    public function index(): View
    {
        return view('admin.cms.pages.index', [
            'pages' => Page::query()->latest()->paginate(25),
        ]);
    }

    public function create(): View
    {
        return view('admin.cms.pages.edit', ['page' => new Page()]);
    }

    public function store(Request $request, PagePublisher $publisher, AuditLogger $audit): RedirectResponse
    {
        $page = $publisher->save(new Page(), $this->validated($request), $request->user());
        $audit->log('cms.page_created', $request->user(), $page);

        return redirect()->route('admin.pages.edit', $page)->with('status', 'page-created');
    }

    public function edit(Page $page): View
    {
        return view('admin.cms.pages.edit', ['page' => $page->load('versions')]);
    }

    public function update(Request $request, Page $page, PagePublisher $publisher, AuditLogger $audit): RedirectResponse
    {
        $publisher->save($page, $this->validated($request, $page), $request->user());
        $audit->log('cms.page_updated', $request->user(), $page);

        return back()->with('status', 'page-updated');
    }

    private function validated(Request $request, ?Page $page = null): array
    {
        $slugRule = $page?->exists ? 'unique:pages,slug,'.$page->id : 'unique:pages,slug';
        $attributes = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'slug' => ['nullable', 'string', 'max:180', $slugRule],
            'status' => ['required', 'in:draft,published,scheduled,unpublished'],
            'body' => ['nullable', 'string'],
            'seo_title' => ['nullable', 'string', 'max:160'],
            'meta_description' => ['nullable', 'string', 'max:320'],
            'canonical_url' => ['nullable', 'url', 'max:255'],
            'is_legal' => ['nullable', 'boolean'],
            'published_at' => ['nullable', 'date'],
        ]);

        $attributes['slug'] = $attributes['slug'] ?: Str::slug($attributes['title']);
        $attributes['is_legal'] = (bool) ($attributes['is_legal'] ?? false);
        $attributes['published_at'] = $attributes['published_at'] ?? ($attributes['status'] === 'published' ? now() : null);

        return $attributes;
    }
}
