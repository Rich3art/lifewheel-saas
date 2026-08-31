<x-layouts.app title="{{ $topic->title }}">
    <main class="mx-auto max-w-5xl px-6 py-10">
        <div class="border-b border-white/10 pb-8">
            <p class="text-sm text-zinc-400">{{ $topic->category_name }}</p>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight">{{ $topic->title }}</h1>
            <p class="mt-2 text-sm text-zinc-500">By {{ $topic->author_name }}</p>
            <p class="mt-5 whitespace-pre-line text-sm leading-6 text-zinc-300">{{ $topic->body }}</p>
        </div>

        <section class="mt-8 space-y-4">
            @foreach ($replies as $reply)
                <article class="rounded-2xl border border-white/10 bg-white/[0.03] p-5">
                    <p class="text-sm font-semibold">{{ $reply->author_name }}</p>
                    <p class="mt-3 whitespace-pre-line text-sm leading-6 text-zinc-300">{{ $reply->body }}</p>
                    <form method="POST" action="{{ route('plugins.forum.reports.store') }}" class="mt-4">
                        @csrf
                        <input type="hidden" name="reportable_type" value="reply">
                        <input type="hidden" name="reportable_id" value="{{ $reply->id }}">
                        <input type="hidden" name="reason" value="Member report">
                        <button class="text-xs text-zinc-500 underline">Report reply</button>
                    </form>
                </article>
            @endforeach
        </section>

        <section class="mt-6 rounded-2xl border border-white/10 bg-white/[0.03] p-6">
            <h2 class="text-lg font-semibold">Reply</h2>
            <form method="POST" action="{{ route('plugins.forum.replies.store', $topic->id) }}" class="mt-4 space-y-4">
                @csrf
                <textarea name="body" rows="5" required class="w-full rounded-xl border border-white/10 bg-zinc-950/60 px-4 py-3 text-sm text-zinc-100">{{ old('body') }}</textarea>
                <button class="rounded-xl bg-white px-4 py-3 text-sm font-semibold text-zinc-950">Post reply</button>
            </form>
        </section>
    </main>
</x-layouts.app>
