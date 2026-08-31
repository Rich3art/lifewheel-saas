<x-layouts.app title="Habit">
    <main class="mx-auto max-w-6xl px-6 py-10">
        <a href="{{ route('plugins.habits.index') }}" class="text-sm text-zinc-400">Habits</a>
        <div class="mt-2 border-b border-white/10 pb-8">
            <p class="text-sm text-zinc-500">{{ ucfirst($habit->type) }} / {{ ucfirst($habit->status) }}</p>
            <h1 class="mt-2 text-3xl font-semibold">{{ $habit->name }}</h1>
            <p class="mt-3 max-w-3xl text-sm leading-6 text-zinc-400">{{ $habit->notes }}</p>
        </div>

        <div class="mt-8 grid gap-6 xl:grid-cols-[1fr_360px]">
            <section class="rounded-2xl border border-white/10 bg-white/[0.03] p-6">
                <h2 class="text-lg font-semibold">Update habit</h2>
                <form method="POST" action="{{ route('plugins.habits.habits.update', $habit->id) }}" class="mt-6 space-y-4">
                    @csrf
                    @method('PUT')
                    <input name="name" required value="{{ old('name', $habit->name) }}" class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                    <div class="grid gap-3 sm:grid-cols-3">
                        <select name="type" class="rounded-xl border border-white/10 bg-zinc-900 px-4 py-3 text-sm">
                            @foreach (['positive', 'quit', 'numeric'] as $type)
                                <option value="{{ $type }}" @selected(old('type', $habit->type) === $type)>{{ ucfirst($type) }}</option>
                            @endforeach
                        </select>
                        <select name="status" class="rounded-xl border border-white/10 bg-zinc-900 px-4 py-3 text-sm">
                            @foreach (['active', 'paused', 'archived'] as $status)
                                <option value="{{ $status }}" @selected(old('status', $habit->status) === $status)>{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                        <input name="target_count" type="number" min="1" max="99" value="{{ old('target_count', $habit->target_count) }}" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                        <input name="target_value" type="number" step="0.01" value="{{ old('target_value', $habit->target_value) }}" placeholder="Numeric target" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                        <input name="unit" value="{{ old('unit', $habit->unit) }}" placeholder="Unit" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                    </div>
                    <textarea name="notes" rows="3" class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">{{ old('notes', $habit->notes) }}</textarea>
                    <div class="grid grid-cols-4 gap-2 sm:grid-cols-7">
                        @foreach ($weekdays as $day => $label)
                            <label class="flex items-center justify-center gap-2 rounded-xl border border-white/10 px-3 py-2 text-sm text-zinc-300">
                                <input type="checkbox" name="weekdays[]" value="{{ $day }}" @checked(in_array($day, old('weekdays', $habit->weekdays), false))>
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                    <div class="grid gap-2 sm:grid-cols-3">
                        @foreach ($areas as $key => $label)
                            <label class="flex items-center gap-2 rounded-xl border border-white/10 px-3 py-2 text-sm text-zinc-300">
                                <input type="checkbox" name="areas[]" value="{{ $key }}" @checked(in_array($key, old('areas', $habit->areas), true))>
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                    <button class="rounded-xl bg-white px-4 py-3 text-sm font-semibold text-zinc-950">Save habit</button>
                </form>
            </section>

            <aside class="rounded-2xl border border-white/10 bg-white/[0.03] p-6">
                <h2 class="text-lg font-semibold">Log habit</h2>
                <form method="POST" action="{{ route('plugins.habits.logs.store', $habit->id) }}" class="mt-5 space-y-3">
                    @csrf
                    <input name="logged_on" type="date" required value="{{ now()->toDateString() }}" class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                    <label class="flex items-center gap-2 text-sm text-zinc-300"><input type="checkbox" name="completed" value="1" checked> Completed</label>
                    <input name="value" type="number" step="0.01" placeholder="Value {{ $habit->unit }}" class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                    <input name="mood" placeholder="Mood note" class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                    <textarea name="notes" rows="3" placeholder="Notes" class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm"></textarea>
                    <button class="w-full rounded-xl bg-white px-4 py-3 text-sm font-semibold text-zinc-950">Save log</button>
                </form>
            </aside>
        </div>

        <section class="mt-6 rounded-2xl border border-white/10 bg-white/[0.03] p-6">
            <h2 class="text-lg font-semibold">Recent logs</h2>
            <div class="mt-4 space-y-3">
                @forelse ($logs as $log)
                    <div class="rounded-xl border border-white/10 px-4 py-3 text-sm">
                        <div class="flex items-center justify-between gap-3">
                            <span>{{ $log->logged_on }}</span>
                            <span class="{{ $log->completed ? 'text-emerald-300' : 'text-zinc-500' }}">{{ $log->completed ? 'Completed' : 'Missed' }}</span>
                        </div>
                        @if ($log->value)<p class="mt-2 text-zinc-400">{{ $log->value }} {{ $habit->unit }}</p>@endif
                        @if ($log->notes)<p class="mt-2 text-zinc-400">{{ $log->notes }}</p>@endif
                    </div>
                @empty
                    <p class="text-sm text-zinc-400">No logs yet.</p>
                @endforelse
            </div>
        </section>
    </main>
</x-layouts.app>
