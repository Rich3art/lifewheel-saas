<x-layouts.app title="AI Coach">
    <main class="mx-auto max-w-6xl px-6 py-10">
        <div class="flex flex-col gap-4 border-b border-white/10 pb-8 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm text-zinc-400">Ask My Life</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight">AI Coach</h1>
            </div>
            <a href="{{ route('member.dashboard') }}" class="rounded-xl border border-white/10 px-4 py-2 text-center text-sm text-zinc-200">Dashboard</a>
        </div>

        <section class="mt-8 rounded-2xl border border-white/10 bg-white/[0.03] p-6">
            <h2 class="text-lg font-semibold">Start a coaching conversation</h2>
            <form method="POST" action="{{ route('plugins.ai-coach.conversations.store') }}" class="mt-5 space-y-4">
                @csrf
                <textarea name="question" rows="4" required minlength="8" maxlength="1000" class="w-full rounded-xl border border-white/10 bg-zinc-950/60 px-4 py-3 text-sm text-zinc-100 outline-none focus:border-white/30" placeholder="Ask something like: What am I avoiding lately, based on my LifeWheel history?">{{ old('question') }}</textarea>
                @error('question')
                    <p class="text-sm text-red-300">{{ $message }}</p>
                @enderror
                <button class="rounded-xl bg-white px-4 py-3 text-sm font-semibold text-zinc-950">Create conversation</button>
            </form>
        </section>

        <section class="mt-6 rounded-2xl border border-white/10 bg-white/[0.03] p-6">
            <h2 class="text-lg font-semibold">Conversations</h2>
            <div class="mt-4 space-y-3">
                @forelse ($conversations as $conversation)
                    <a href="{{ route('plugins.ai-coach.conversations.show', $conversation->id) }}" class="flex flex-col gap-2 rounded-xl border border-white/10 px-4 py-3 text-sm transition hover:bg-white/[0.06] sm:flex-row sm:items-center sm:justify-between">
                        <span>{{ $conversation->title }}</span>
                        <span class="text-zinc-500">{{ \Illuminate\Support\Carbon::parse($conversation->updated_at)->format('Y-m-d H:i') }}</span>
                    </a>
                @empty
                    <p class="text-sm text-zinc-400">No coaching conversations yet.</p>
                @endforelse
            </div>
            <div class="mt-6">{{ $conversations->links() }}</div>
        </section>
    </main>
</x-layouts.app>
