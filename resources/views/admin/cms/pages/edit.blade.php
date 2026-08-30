<x-layouts.app title="{{ $page->exists ? 'Edit page' : 'Create page' }}">
    <main class="mx-auto max-w-5xl px-6 py-10">
        <a href="{{ route('admin.pages.index') }}" class="text-sm text-zinc-400">Pages</a>
        <h1 class="mt-2 text-3xl font-semibold">{{ $page->exists ? 'Edit page' : 'Create page' }}</h1>
        <form method="POST" action="{{ $page->exists ? route('admin.pages.update', $page) : route('admin.pages.store') }}" class="mt-8 space-y-5">
            @csrf
            @if ($page->exists) @method('PUT') @endif
            <div class="grid gap-4 lg:grid-cols-2">
                <input name="title" value="{{ old('title', $page->title) }}" placeholder="Title" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                <input name="slug" value="{{ old('slug', $page->slug) }}" placeholder="slug" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
            </div>
            <textarea name="body" rows="12" placeholder="Page content" class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">{{ old('body', $page->body) }}</textarea>
            <div class="grid gap-4 lg:grid-cols-3">
                <select name="status" class="rounded-xl border border-white/10 bg-zinc-900 px-4 py-3 text-sm">
                    @foreach (['draft', 'published', 'scheduled', 'unpublished'] as $status)
                        <option value="{{ $status }}" @selected(old('status', $page->status ?: 'draft') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
                <input name="published_at" type="datetime-local" value="{{ old('published_at', $page->published_at?->format('Y-m-d\TH:i')) }}" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                <label class="flex items-center gap-2 text-sm text-zinc-300"><input type="checkbox" name="is_legal" value="1" @checked(old('is_legal', $page->is_legal))> Legal/versioned policy</label>
            </div>
            <div class="grid gap-4 lg:grid-cols-2">
                <input name="seo_title" value="{{ old('seo_title', $page->seo_title) }}" placeholder="SEO title" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                <input name="canonical_url" value="{{ old('canonical_url', $page->canonical_url) }}" placeholder="Canonical URL" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
            </div>
            <textarea name="meta_description" rows="2" placeholder="Meta description" class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">{{ old('meta_description', $page->meta_description) }}</textarea>
            <button class="rounded-xl bg-white px-4 py-3 text-sm font-semibold text-zinc-950">Save page</button>
        </form>

        @if ($page->exists)
            <section class="mt-8 rounded-2xl border border-white/10 bg-white/[0.03] p-6">
                <h2 class="text-lg font-semibold">Versions</h2>
                <div class="mt-4 space-y-2 text-sm text-zinc-400">
                    @foreach ($page->versions->sortByDesc('version') as $version)
                        <div>Version {{ $version->version }} - {{ $version->status }} - {{ $version->created_at?->format('Y-m-d H:i') }}</div>
                    @endforeach
                </div>
            </section>
        @endif
    </main>
</x-layouts.app>
