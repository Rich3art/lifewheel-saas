<x-layouts.app title="XP">
    <main class="mx-auto max-w-5xl px-6 py-10">
        <div class="flex flex-col gap-4 border-b border-white/10 pb-8 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm text-zinc-400">Gamification</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight">XP Ledger</h1>
            </div>
            <a href="{{ route('member.dashboard') }}" class="rounded-xl border border-white/10 px-4 py-2 text-center text-sm text-zinc-200">Dashboard</a>
        </div>

        <section class="mt-8 grid gap-4 sm:grid-cols-3">
            <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-6">
                <p class="text-sm text-zinc-400">Total XP</p>
                <p class="mt-3 text-4xl font-semibold">{{ $totalXp }}</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-6">
                <p class="text-sm text-zinc-400">Level</p>
                <p class="mt-3 text-4xl font-semibold">{{ $level }}</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-6">
                <p class="text-sm text-zinc-400">Next Level</p>
                <p class="mt-3 text-4xl font-semibold">{{ $nextLevelXp }} XP</p>
            </div>
        </section>

        <section class="mt-6 rounded-2xl border border-white/10 bg-white/[0.03] p-6">
            <h2 class="text-lg font-semibold">Recent XP events</h2>
            <div class="mt-4 space-y-3">
                @forelse ($events as $event)
                    <div class="flex flex-col gap-2 rounded-xl border border-white/10 px-4 py-3 text-sm sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="font-semibold">{{ str_replace('_', ' ', $event->event_type) }}</p>
                            <p class="text-xs text-zinc-500">{{ $event->source_type }} #{{ $event->source_id }}</p>
                        </div>
                        <div class="text-zinc-300">{{ $event->xp > 0 ? '+' : '' }}{{ $event->xp }} XP</div>
                    </div>
                @empty
                    <p class="text-sm text-zinc-400">No XP events yet.</p>
                @endforelse
            </div>
        </section>
    </main>
</x-layouts.app>
