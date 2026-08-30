<x-layouts.app title="Blog taxonomy">
    <main class="mx-auto max-w-5xl px-6 py-10">
        <a href="{{ route('admin.blog.index') }}" class="text-sm text-zinc-400">Blog</a>
        <h1 class="mt-2 text-3xl font-semibold">Taxonomy</h1>
        <div class="mt-8 grid gap-6 lg:grid-cols-2">
            <section class="rounded-2xl border border-white/10 bg-white/[0.03] p-6">
                <h2 class="text-lg font-semibold">Categories</h2>
                <form method="POST" action="{{ route('admin.blog.categories.store') }}" class="mt-5 space-y-3">
                    @csrf
                    <input name="name" placeholder="Category name" class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                    <input name="slug" placeholder="slug" class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                    <textarea name="description" rows="2" placeholder="Description" class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm"></textarea>
                    <button class="rounded-xl bg-white px-4 py-3 text-sm font-semibold text-zinc-950">Create category</button>
                </form>
                <div class="mt-5 space-y-2 text-sm text-zinc-400">
                    @foreach ($categories as $category)
                        <div>{{ $category->name }} - {{ $category->slug }}</div>
                    @endforeach
                </div>
            </section>
            <section class="rounded-2xl border border-white/10 bg-white/[0.03] p-6">
                <h2 class="text-lg font-semibold">Tags</h2>
                <form method="POST" action="{{ route('admin.blog.tags.store') }}" class="mt-5 space-y-3">
                    @csrf
                    <input name="name" placeholder="Tag name" class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                    <input name="slug" placeholder="slug" class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                    <button class="rounded-xl bg-white px-4 py-3 text-sm font-semibold text-zinc-950">Create tag</button>
                </form>
                <div class="mt-5 space-y-2 text-sm text-zinc-400">
                    @foreach ($tags as $tag)
                        <div>{{ $tag->name }} - {{ $tag->slug }}</div>
                    @endforeach
                </div>
            </section>
        </div>
    </main>
</x-layouts.app>
