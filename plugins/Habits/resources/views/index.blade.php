<x-layouts.app title="Habits">
    <main class="mx-auto max-w-7xl px-6 py-10">
        <div class="flex flex-col gap-4 border-b border-white/10 pb-8 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-sm text-zinc-400">Behavior system</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight">Habits</h1>
            </div>
            <a href="{{ route('member.dashboard') }}" class="rounded-xl border border-white/10 px-4 py-2 text-center text-sm text-zinc-200">Dashboard</a>
        </div>

        <div class="mt-8 grid gap-6 xl:grid-cols-[420px_1fr]">
            <section class="rounded-2xl border border-white/10 bg-white/[0.03] p-6">
                <h2 class="text-lg font-semibold">Create habit</h2>
                <form method="POST" action="{{ route('plugins.habits.habits.store') }}" class="mt-6 space-y-4">
                    @csrf
                    <input name="name" required value="{{ old('name') }}" placeholder="Habit name" class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <select name="type" class="rounded-xl border border-white/10 bg-zinc-900 px-4 py-3 text-sm">
                            <option value="positive">Positive</option>
                            <option value="quit">Quit</option>
                            <option value="numeric">Numeric</option>
                        </select>
                        <input name="target_count" type="number" min="1" max="99" value="{{ old('target_count', 1) }}" placeholder="Times per day" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                        <input name="target_value" type="number" step="0.01" value="{{ old('target_value') }}" placeholder="Numeric target" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                        <input name="unit" value="{{ old('unit') }}" placeholder="Unit" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                    </div>
                    <input type="hidden" name="status" value="active">
                    <textarea name="notes" rows="3" placeholder="Notes" class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">{{ old('notes') }}</textarea>
                    <div>
                        <p class="text-sm text-zinc-400">Weekdays</p>
                        <div class="mt-2 grid grid-cols-4 gap-2 sm:grid-cols-7">
                            @foreach ($weekdays as $day => $label)
                                <label class="flex items-center justify-center gap-2 rounded-xl border border-white/10 px-3 py-2 text-sm text-zinc-300">
                                    <input type="checkbox" name="weekdays[]" value="{{ $day }}" checked>
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="grid gap-2 sm:grid-cols-3">
                        @foreach ($areas as $key => $label)
                            <label class="flex items-center gap-2 rounded-xl border border-white/10 px-3 py-2 text-sm text-zinc-300">
                                <input type="checkbox" name="areas[]" value="{{ $key }}">
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                    <button class="rounded-xl bg-white px-4 py-3 text-sm font-semibold text-zinc-950">Create habit</button>
                </form>
            </section>

            <section class="rounded-2xl border border-white/10 bg-white/[0.03] p-6">
                <h2 class="text-lg font-semibold">Today</h2>
                <div class="mt-6 grid gap-3 lg:grid-cols-2">
                    @forelse ($habits as $habit)
                        <article class="rounded-xl border border-white/10 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <a href="{{ route('plugins.habits.show', $habit->id) }}" class="font-semibold">{{ $habit->name }}</a>
                                    <p class="mt-1 text-xs text-zinc-500">{{ ucfirst($habit->type) }} / {{ $habit->target_count }}x</p>
                                </div>
                                @if ($habit->adherence !== null)
                                    <span class="rounded-full border border-white/10 px-2 py-1 text-xs text-zinc-300">{{ $habit->adherence }}%</span>
                                @endif
                            </div>
                            <form method="POST" action="{{ route('plugins.habits.logs.store', $habit->id) }}" class="mt-4 grid gap-2">
                                @csrf
                                <input type="hidden" name="logged_on" value="{{ now()->toDateString() }}">
                                <input type="hidden" name="completed" value="1">
                                @if ($habit->type === 'numeric')
                                    <input name="value" type="number" step="0.01" placeholder="Value {{ $habit->unit }}" class="rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm">
                                @endif
                                <button class="rounded-xl {{ $habit->logged_today ? 'border border-emerald-400/30 text-emerald-200' : 'bg-white text-zinc-950' }} px-4 py-3 text-sm font-semibold">
                                    {{ $habit->logged_today ? 'Logged today' : 'Log today' }}
                                </button>
                            </form>
                        </article>
                    @empty
                        <p class="rounded-xl border border-white/10 px-4 py-6 text-sm text-zinc-400">No active habits yet.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </main>
</x-layouts.app>
