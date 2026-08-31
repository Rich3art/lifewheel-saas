<x-layouts.app title="Gamification">
    <main class="mx-auto max-w-5xl px-6 py-10">
        <a href="{{ route('admin.dashboard') }}" class="text-sm text-zinc-400">Admin</a>
        <h1 class="mt-2 text-3xl font-semibold">Gamification rules</h1>

        <div class="mt-8 space-y-4">
            @forelse ($rules as $rule)
                <form method="POST" action="{{ route('plugins.gamification.admin.rules.update', $rule->id) }}" class="grid gap-4 rounded-2xl border border-white/10 bg-white/[0.03] p-5 sm:grid-cols-[1fr_120px_160px_120px] sm:items-center">
                    @csrf
                    @method('PUT')
                    <div>
                        <h2 class="font-semibold">{{ $rule->label }}</h2>
                        <p class="mt-1 text-xs text-zinc-500">{{ $rule->event_type }}</p>
                    </div>
                    <input name="xp" type="number" value="{{ $rule->xp }}" class="rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm">
                    <input name="cooldown_hours" type="number" min="0" value="{{ $rule->cooldown_hours }}" class="rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm">
                    <label class="flex items-center gap-2 text-sm text-zinc-300"><input name="enabled" type="checkbox" value="1" @checked($rule->enabled)> Enabled</label>
                    <button class="sm:col-start-4 rounded-xl bg-white px-4 py-2 text-sm font-semibold text-zinc-950">Save</button>
                </form>
            @empty
                <p class="rounded-2xl border border-white/10 bg-white/[0.03] p-6 text-sm text-zinc-400">No gamification rules yet. Activate the plugin to seed defaults.</p>
            @endforelse
        </div>
    </main>
</x-layouts.app>
