<x-layouts.app title="{{ $post->exists ? 'Edit post' : 'Create post' }}">
    <main class="mx-auto max-w-5xl px-6 py-10">
        <a href="{{ route('admin.blog.index') }}" class="text-sm text-zinc-400">Blog</a>
        <h1 class="mt-2 text-3xl font-semibold">{{ $post->exists ? 'Edit post' : 'Create post' }}</h1>
        <form method="POST" action="{{ $post->exists ? route('admin.blog.update', $post) : route('admin.blog.store') }}" class="mt-8 space-y-5">
            @csrf
            @if ($post->exists) @method('PUT') @endif
            <div class="grid gap-4 lg:grid-cols-2">
                <input name="title" value="{{ old('title', $post->title) }}" placeholder="Title" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                <input name="slug" value="{{ old('slug', $post->slug) }}" placeholder="slug" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
            </div>
            <textarea name="excerpt" rows="2" placeholder="Excerpt" class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">{{ old('excerpt', $post->excerpt) }}</textarea>
            <textarea name="body" rows="14" placeholder="Post content" class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">{{ old('body', $post->body) }}</textarea>
            <div class="grid gap-4 lg:grid-cols-3">
                <select name="status" class="rounded-xl border border-white/10 bg-zinc-900 px-4 py-3 text-sm">
                    @foreach (['draft', 'published', 'scheduled', 'unpublished'] as $status)
                        <option value="{{ $status }}" @selected(old('status', $post->status ?: 'draft') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
                <input name="published_at" type="datetime-local" value="{{ old('published_at', $post->published_at?->format('Y-m-d\TH:i')) }}" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                <input name="featured_image_path" value="{{ old('featured_image_path', $post->featured_image_path) }}" placeholder="Featured image path" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-4">
                    <h2 class="text-sm font-semibold">Categories</h2>
                    <div class="mt-3 grid gap-2">
                        @foreach ($categories as $category)
                            <label class="flex items-center gap-2 text-sm text-zinc-300"><input type="checkbox" name="categories[]" value="{{ $category->id }}" @checked($post->categories->contains($category))> {{ $category->name }}</label>
                        @endforeach
                    </div>
                </div>
                <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-4">
                    <h2 class="text-sm font-semibold">Tags</h2>
                    <div class="mt-3 grid gap-2">
                        @foreach ($tags as $tag)
                            <label class="flex items-center gap-2 text-sm text-zinc-300"><input type="checkbox" name="tags[]" value="{{ $tag->id }}" @checked($post->tags->contains($tag))> {{ $tag->name }}</label>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="grid gap-4 lg:grid-cols-2">
                <input name="seo_title" value="{{ old('seo_title', $post->seo_title) }}" placeholder="SEO title" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                <input name="canonical_url" value="{{ old('canonical_url', $post->canonical_url) }}" placeholder="Canonical URL" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
            </div>
            <textarea name="meta_description" rows="2" placeholder="Meta description" class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">{{ old('meta_description', $post->meta_description) }}</textarea>
            <button class="rounded-xl bg-white px-4 py-3 text-sm font-semibold text-zinc-950">Save post</button>
        </form>

        @if ($post->exists)
            <section class="mt-8 rounded-2xl border border-white/10 bg-white/[0.03] p-6">
                <h2 class="text-lg font-semibold">Revisions</h2>
                <div class="mt-4 space-y-2 text-sm text-zinc-400">
                    @foreach ($post->revisions->sortByDesc('created_at') as $revision)
                        <div>{{ $revision->created_at?->format('Y-m-d H:i') }} - {{ $revision->status }} - {{ $revision->title }}</div>
                    @endforeach
                </div>
            </section>
        @endif
    </main>
</x-layouts.app>
