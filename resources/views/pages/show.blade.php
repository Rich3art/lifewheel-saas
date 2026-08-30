<x-layouts.app title="{{ $version->seo_title ?: $version->title }}">
    <main class="mx-auto max-w-3xl px-6 py-12">
        <a href="{{ route('home') }}" class="text-sm text-zinc-400">LifeWheel SaaS</a>
        <article class="mt-8">
            <h1 class="text-4xl font-semibold tracking-tight">{{ $version->title }}</h1>
            @if ($page->is_legal)
                <p class="mt-3 text-sm text-zinc-500">Version {{ $version->version }} published {{ $version->published_at?->format('Y-m-d') }}</p>
            @endif
            <div class="mt-8 space-y-5 text-base leading-8 text-zinc-300">
                {!! nl2br(e($version->body)) !!}
            </div>
        </article>
    </main>
</x-layouts.app>
