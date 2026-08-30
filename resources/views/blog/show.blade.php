<x-layouts.app title="{{ $post->seo_title ?: $post->title }}">
    <main class="mx-auto max-w-3xl px-6 py-12">
        <a href="{{ route('blog.index') }}" class="text-sm text-zinc-400">Blog</a>
        <article class="mt-8">
            <h1 class="text-4xl font-semibold tracking-tight">{{ $post->title }}</h1>
            <p class="mt-4 text-sm text-zinc-500">{{ $post->published_at?->format('Y-m-d') }} @if($post->author) by {{ $post->author->name }} @endif</p>
            @if ($post->excerpt)
                <p class="mt-6 text-lg leading-8 text-zinc-300">{{ $post->excerpt }}</p>
            @endif
            <div class="mt-8 space-y-5 text-base leading-8 text-zinc-300">
                {!! nl2br(e($post->body)) !!}
            </div>
        </article>
    </main>
</x-layouts.app>
