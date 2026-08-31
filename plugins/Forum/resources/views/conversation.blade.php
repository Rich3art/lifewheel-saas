<x-layouts.app title="Conversation">
    <main class="mx-auto max-w-5xl px-6 py-10">
        <div class="flex flex-col gap-4 border-b border-white/10 pb-8 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm text-zinc-400">Messages</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight">{{ $conversation->subject ?: 'Conversation' }}</h1>
            </div>
            <a href="{{ route('plugins.forum.messages.index') }}" class="rounded-xl border border-white/10 px-4 py-2 text-center text-sm text-zinc-200">All messages</a>
        </div>

        <section class="mt-8 space-y-4">
            @foreach ($messages as $message)
                <article class="rounded-2xl border border-white/10 bg-white/[0.03] p-5">
                    <p class="text-sm font-semibold">{{ $message->sender_name }}</p>
                    <p class="mt-3 whitespace-pre-line text-sm leading-6 text-zinc-300">{{ $message->body }}</p>
                    <p class="mt-4 text-xs text-zinc-500">{{ \Illuminate\Support\Carbon::parse($message->created_at)->format('Y-m-d H:i') }}</p>
                </article>
            @endforeach
        </section>

        <section class="mt-6 rounded-2xl border border-white/10 bg-white/[0.03] p-6">
            <form method="POST" action="{{ route('plugins.forum.messages.messages.store', $conversation->id) }}" class="space-y-4">
                @csrf
                <textarea name="body" rows="4" required class="w-full rounded-xl border border-white/10 bg-zinc-950/60 px-4 py-3 text-sm text-zinc-100"></textarea>
                <button class="rounded-xl bg-white px-4 py-3 text-sm font-semibold text-zinc-950">Send reply</button>
            </form>
        </section>
    </main>
</x-layouts.app>
