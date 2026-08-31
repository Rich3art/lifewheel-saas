<x-layouts.app title="Messages">
    <main class="mx-auto max-w-6xl px-6 py-10">
        <div class="flex flex-col gap-4 border-b border-white/10 pb-8 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm text-zinc-400">Private conversations</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight">Messages</h1>
            </div>
            <a href="{{ route('plugins.forum.index') }}" class="rounded-xl border border-white/10 px-4 py-2 text-center text-sm text-zinc-200">Community</a>
        </div>

        <section class="mt-8 rounded-2xl border border-white/10 bg-white/[0.03] p-6">
            <h2 class="text-lg font-semibold">Start message</h2>
            <form method="POST" action="{{ route('plugins.forum.messages.conversations.store') }}" class="mt-5 grid gap-4 md:grid-cols-[180px_1fr_auto]">
                @csrf
                <input name="recipient_id" required type="number" min="1" placeholder="User ID" class="rounded-xl border border-white/10 bg-zinc-950/60 px-4 py-3 text-sm text-zinc-100">
                <input name="body" required minlength="2" maxlength="5000" placeholder="Message" class="rounded-xl border border-white/10 bg-zinc-950/60 px-4 py-3 text-sm text-zinc-100">
                <button class="rounded-xl bg-white px-4 py-3 text-sm font-semibold text-zinc-950">Send</button>
            </form>
        </section>

        <section class="mt-6 rounded-2xl border border-white/10 bg-white/[0.03] p-6">
            <h2 class="text-lg font-semibold">Conversations</h2>
            <div class="mt-4 space-y-3">
                @forelse ($conversations as $conversation)
                    <a href="{{ route('plugins.forum.messages.show', $conversation->conversation_id) }}" class="block rounded-xl border border-white/10 px-4 py-3 text-sm transition hover:bg-white/[0.06]">
                        {{ $conversation->subject ?: 'Conversation' }}
                        <span class="ml-2 text-zinc-500">{{ \Illuminate\Support\Carbon::parse($conversation->updated_at)->format('Y-m-d H:i') }}</span>
                    </a>
                @empty
                    <p class="text-sm text-zinc-400">No messages yet.</p>
                @endforelse
            </div>
            <div class="mt-6">{{ $conversations->links() }}</div>
        </section>
    </main>
</x-layouts.app>
