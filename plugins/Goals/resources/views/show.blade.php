<x-layouts.app title="Goal">
    <main class="mx-auto max-w-6xl px-6 py-10">
        <a href="{{ route('plugins.goals.index') }}" class="text-sm text-zinc-400">Goals</a>
        <div class="mt-2 flex flex-col gap-4 border-b border-white/10 pb-8 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-sm text-zinc-500">{{ ucfirst($goal->status) }}</p>
                <h1 class="mt-2 text-3xl font-semibold">{{ $goal->name }}</h1>
                <p class="mt-3 max-w-3xl text-sm leading-6 text-zinc-400">{{ $goal->why }}</p>
            </div>
            @if ($goal->progress_percentage !== null)
                <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-5 text-center">
                    <div class="text-4xl font-semibold">{{ $goal->progress_percentage }}%</div>
                    <p class="mt-1 text-xs text-zinc-500">measured progress</p>
                </div>
            @endif
        </div>

        <div class="mt-8 grid gap-6 xl:grid-cols-[1fr_360px]">
            <section class="rounded-2xl border border-white/10 bg-white/[0.03] p-6">
                <h2 class="text-lg font-semibold">Update goal</h2>
                <form method="POST" action="{{ route('plugins.goals.goals.update', $goal->id) }}" class="mt-6 space-y-4">
                    @csrf
                    @method('PUT')
                    <input name="name" required value="{{ old('name', $goal->name) }}" class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                    <textarea name="why" rows="3" class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">{{ old('why', $goal->why) }}</textarea>
                    <textarea name="success_criterion" rows="2" class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">{{ old('success_criterion', $goal->success_criterion) }}</textarea>
                    <div class="grid gap-3 sm:grid-cols-3">
                        <select name="status" class="rounded-xl border border-white/10 bg-zinc-900 px-4 py-3 text-sm">
                            @foreach (['active', 'paused', 'completed', 'archived'] as $status)
                                <option value="{{ $status }}" @selected(old('status', $goal->status) === $status)>{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                        <input name="measure" value="{{ old('measure', $goal->measure) }}" placeholder="Measure" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                        <input name="unit" value="{{ old('unit', $goal->unit) }}" placeholder="Unit" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                        <input name="baseline" type="number" step="0.01" value="{{ old('baseline', $goal->baseline) }}" placeholder="Baseline" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                        <input name="current" type="number" step="0.01" value="{{ old('current', $goal->current) }}" placeholder="Current" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                        <input name="target" type="number" step="0.01" value="{{ old('target', $goal->target) }}" placeholder="Target" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                        <input name="due_date" type="date" value="{{ old('due_date', $goal->due_date) }}" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                    </div>
                    <div class="grid gap-2 sm:grid-cols-3">
                        @foreach ($areas as $key => $label)
                            <label class="flex items-center gap-2 rounded-xl border border-white/10 px-3 py-2 text-sm text-zinc-300">
                                <input type="checkbox" name="areas[]" value="{{ $key }}" @checked(in_array($key, old('areas', $goal->areas), true))>
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                    <button class="rounded-xl bg-white px-4 py-3 text-sm font-semibold text-zinc-950">Save goal</button>
                </form>
            </section>

            <aside class="space-y-6">
                <section class="rounded-2xl border border-white/10 bg-white/[0.03] p-6">
                    <h2 class="text-lg font-semibold">Record progress</h2>
                    <form method="POST" action="{{ route('plugins.goals.progress.store', $goal->id) }}" class="mt-5 space-y-3">
                        @csrf
                        <input name="value" type="number" step="0.01" required placeholder="Current value" class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                        <input name="recorded_on" type="date" required value="{{ now()->toDateString() }}" class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                        <textarea name="notes" rows="3" placeholder="Notes" class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm"></textarea>
                        <button class="w-full rounded-xl bg-white px-4 py-3 text-sm font-semibold text-zinc-950">Record</button>
                    </form>
                </section>

                <section class="rounded-2xl border border-white/10 bg-white/[0.03] p-6">
                    <h2 class="text-lg font-semibold">Add milestone</h2>
                    <form method="POST" action="{{ route('plugins.goals.milestones.store', $goal->id) }}" class="mt-5 space-y-3">
                        @csrf
                        <input name="name" required placeholder="Milestone" class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                        <input name="due_date" type="date" class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                        <textarea name="notes" rows="3" placeholder="Notes" class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm"></textarea>
                        <button class="w-full rounded-xl border border-white/10 px-4 py-3 text-sm text-zinc-200">Add</button>
                    </form>
                </section>
            </aside>
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            <section class="rounded-2xl border border-white/10 bg-white/[0.03] p-6">
                <h2 class="text-lg font-semibold">Milestones</h2>
                <div class="mt-4 space-y-3">
                    @forelse ($milestones as $milestone)
                        <div class="rounded-xl border border-white/10 px-4 py-3">
                            <div class="flex gap-3 sm:items-center sm:justify-between">
                                <div>
                                    <h3 class="text-sm font-semibold">{{ $milestone->name }}</h3>
                                    <p class="mt-1 text-xs text-zinc-500">{{ $milestone->due_date ?: 'No due date' }}</p>
                                </div>
                                @if (! $milestone->completed_at)
                                    <form method="POST" action="{{ route('plugins.goals.milestones.complete', [$goal->id, $milestone->id]) }}">
                                        @csrf
                                        @method('PUT')
                                        <button class="rounded-xl border border-white/10 px-3 py-2 text-xs">Complete</button>
                                    </form>
                                @else
                                    <span class="text-xs text-emerald-300">Completed</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-zinc-400">No milestones yet.</p>
                    @endforelse
                </div>
            </section>

            <section class="rounded-2xl border border-white/10 bg-white/[0.03] p-6">
                <h2 class="text-lg font-semibold">Progress records</h2>
                <div class="mt-4 space-y-3">
                    @forelse ($progressRecords as $record)
                        <div class="rounded-xl border border-white/10 px-4 py-3 text-sm">
                            <div class="font-semibold">{{ $record->value }} {{ $goal->unit }}</div>
                            <p class="mt-1 text-xs text-zinc-500">{{ $record->recorded_on }}</p>
                            @if ($record->notes)<p class="mt-2 text-zinc-400">{{ $record->notes }}</p>@endif
                        </div>
                    @empty
                        <p class="text-sm text-zinc-400">No progress records yet.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </main>
</x-layouts.app>
