<x-layouts.app title="Blog">
    <main class="mx-auto max-w-5xl px-6 py-12">
        <a href="{{ route('home') }}" class="text-sm text-zinc-400">LifeWheel SaaS</a>
        <h1 class="mt-8 text-4xl font-semibold tracking-tight">Blog</h1>

        <div class="mt-8 grid gap-5">
            @forelse ($posts as $post)
                <article class="rounded-2xl border border-white/10 bg-white/[0.03] p-6">
                    <a href="{{ route('blog.show', $post->slug) }}" class="text-2xl font-semibold">{{ $post->title }}</a>
                    <p class="mt-3 text-sm text-zinc-400">{{ $post->excerpt }}</p>
                    <div class="mt-4 text-xs text-zinc-500">{{ $post->published_at?->format('Y-m-d') }} @if($post->author) by {{ $post->author->name }} @endif</div>
                </article>
            @empty
                <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-6 text-zinc-400">No posts have been published yet.</div>
            @endforelse
        </div>
        <div class="mt-6">{{ $posts->links() }}</div>
    </main>
</x-layouts.app>
