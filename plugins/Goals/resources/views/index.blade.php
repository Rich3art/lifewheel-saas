<x-layouts.app title="Goals">
    <main class="mx-auto max-w-7xl px-6 py-10">
        <div class="flex flex-col gap-4 border-b border-white/10 pb-8 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-sm text-zinc-400">Outcome design</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight">Goals</h1>
            </div>
            <a href="{{ route('member.dashboard') }}" class="rounded-xl border border-white/10 px-4 py-2 text-center text-sm text-zinc-200">Dashboard</a>
        </div>

        <div class="mt-8 grid gap-6 xl:grid-cols-[420px_1fr]">
            <section class="rounded-2xl border border-white/10 bg-white/[0.03] p-6">
                <h2 class="text-lg font-semibold">Create goal</h2>
                <form method="POST" action="{{ route('plugins.goals.goals.store') }}" class="mt-6 space-y-4">
                    @csrf
                    <input name="name" required value="{{ old('name') }}" placeholder="Goal name" class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                    <textarea name="why" rows="3" placeholder="Why this matters" class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">{{ old('why') }}</textarea>
                    <textarea name="success_criterion" rows="2" placeholder="How will you know this is complete?" class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">{{ old('success_criterion') }}</textarea>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <input name="measure" value="{{ old('measure') }}" placeholder="Measure" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                        <input name="unit" value="{{ old('unit') }}" placeholder="Unit" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                        <input name="baseline" type="number" step="0.01" value="{{ old('baseline') }}" placeholder="Baseline" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                        <input name="current" type="number" step="0.01" value="{{ old('current') }}" placeholder="Current" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                        <input name="target" type="number" step="0.01" value="{{ old('target') }}" placeholder="Target" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                        <input name="due_date" type="date" value="{{ old('due_date') }}" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                    </div>
                    <input type="hidden" name="status" value="active">
                    <div class="grid gap-2 sm:grid-cols-3">
                        @foreach ($areas as $key => $label)
                            <label class="flex items-center gap-2 rounded-xl border border-white/10 px-3 py-2 text-sm text-zinc-300">
                                <input type="checkbox" name="areas[]" value="{{ $key }}" @checked(in_array($key, old('areas', []), true))>
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                    <button class="rounded-xl bg-white px-4 py-3 text-sm font-semibold text-zinc-950">Create goal</button>
                </form>
            </section>

            <section class="rounded-2xl border border-white/10 bg-white/[0.03] p-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <h2 class="text-lg font-semibold">Goal list</h2>
                    <div class="flex flex-wrap gap-2 text-sm">
                        @foreach (['active', 'paused', 'completed', 'archived'] as $filter)
                            <a href="{{ route('plugins.goals.index', ['status' => $filter]) }}" class="rounded-xl border border-white/10 px-3 py-2 {{ $status === $filter ? 'bg-white text-zinc-950' : 'text-zinc-300' }}">{{ ucfirst($filter) }}</a>
                        @endforeach
                    </div>
                </div>
                <div class="mt-6 space-y-3">
                    @forelse ($goals as $goal)
                        @php
                            $goalAreas = json_decode((string) $goal->areas, true) ?: [];
                            $progress = \LifeWheel\Plugins\Goals\GoalProgress::percentage((float) $goal->baseline, (float) $goal->current, (float) $goal->target);
                        @endphp
                        <a href="{{ route('plugins.goals.show', $goal->id) }}" class="block rounded-xl border border-white/10 px-4 py-4 transition hover:bg-white/[0.06]">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <h3 class="font-semibold">{{ $goal->name }}</h3>
                                    <p class="mt-2 line-clamp-2 text-sm leading-6 text-zinc-400">{{ $goal->success_criterion ?: $goal->why }}</p>
                                </div>
                                <span class="text-sm text-zinc-500">{{ $goal->due_date ?: 'No due date' }}</span>
                            </div>
                            <div class="mt-4 h-2 overflow-hidden rounded-full bg-white/10">
                                <div class="h-full bg-emerald-300" style="width: {{ $progress ?? 0 }}%"></div>
                            </div>
                            <div class="mt-3 flex flex-wrap gap-2 text-xs text-zinc-500">
                                @foreach ($goalAreas as $area)
                                    <span class="rounded-full border border-white/10 px-2 py-1">{{ $areas[$area] ?? $area }}</span>
                                @endforeach
                                @if ($progress !== null)<span>{{ $progress }}% measured progress</span>@endif
                            </div>
                        </a>
                    @empty
                        <p class="rounded-xl border border-white/10 px-4 py-6 text-sm text-zinc-400">No {{ $status }} goals yet.</p>
                    @endforelse
                </div>
                <div class="mt-6">{{ $goals->links() }}</div>
            </section>
        </div>
    </main>
</x-layouts.app>
