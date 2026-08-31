<x-layouts.app title="Project">
    <main class="mx-auto max-w-6xl px-6 py-10">
        <a href="{{ route('plugins.projects.index') }}" class="text-sm text-zinc-400">Projects</a>
        <div class="mt-2 flex flex-col gap-4 border-b border-white/10 pb-8 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-sm text-zinc-500">{{ ucfirst($project->priority) }} priority / {{ ucfirst($project->status) }}</p>
                <h1 class="mt-2 text-3xl font-semibold">{{ $project->name }}</h1>
                <p class="mt-3 max-w-3xl text-sm leading-6 text-zinc-400">{{ $project->description }}</p>
            </div>
            @if ($completion !== null)
                <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-5 text-center">
                    <div class="text-4xl font-semibold">{{ $completion }}%</div>
                    <p class="mt-1 text-xs text-zinc-500">tasks complete</p>
                </div>
            @endif
        </div>

        <div class="mt-8 grid gap-6 xl:grid-cols-[1fr_360px]">
            <section class="rounded-2xl border border-white/10 bg-white/[0.03] p-6">
                <h2 class="text-lg font-semibold">Update project</h2>
                <form method="POST" action="{{ route('plugins.projects.projects.update', $project->id) }}" class="mt-6 space-y-4">
                    @csrf
                    @method('PUT')
                    <input name="name" required value="{{ old('name', $project->name) }}" class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                    <textarea name="description" rows="4" class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">{{ old('description', $project->description) }}</textarea>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <select name="status" class="rounded-xl border border-white/10 bg-zinc-900 px-4 py-3 text-sm">
                            @foreach (['active', 'paused', 'completed', 'archived'] as $status)
                                <option value="{{ $status }}" @selected(old('status', $project->status) === $status)>{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                        <select name="priority" class="rounded-xl border border-white/10 bg-zinc-900 px-4 py-3 text-sm">
                            @foreach (['low', 'medium', 'high', 'critical'] as $priority)
                                <option value="{{ $priority }}" @selected(old('priority', $project->priority) === $priority)>{{ ucfirst($priority) }}</option>
                            @endforeach
                        </select>
                        <input name="start_date" type="date" value="{{ old('start_date', $project->start_date) }}" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                        <input name="due_date" type="date" value="{{ old('due_date', $project->due_date) }}" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                    </div>
                    <div class="grid gap-2 sm:grid-cols-3">
                        @foreach ($areas as $key => $label)
                            <label class="flex items-center gap-2 rounded-xl border border-white/10 px-3 py-2 text-sm text-zinc-300">
                                <input type="checkbox" name="areas[]" value="{{ $key }}" @checked(in_array($key, old('areas', $project->areas), true))>
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                    <button class="rounded-xl bg-white px-4 py-3 text-sm font-semibold text-zinc-950">Save project</button>
                </form>
            </section>

            <aside class="rounded-2xl border border-white/10 bg-white/[0.03] p-6">
                <h2 class="text-lg font-semibold">Add task</h2>
                <form method="POST" action="{{ route('plugins.projects.tasks.store', $project->id) }}" class="mt-5 space-y-3">
                    @csrf
                    <input name="title" required placeholder="Task title" class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                    <input name="due_date" type="date" class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                    <textarea name="notes" rows="3" placeholder="Notes" class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm"></textarea>
                    <button class="w-full rounded-xl bg-white px-4 py-3 text-sm font-semibold text-zinc-950">Add task</button>
                </form>
            </aside>
        </div>

        <section class="mt-6 rounded-2xl border border-white/10 bg-white/[0.03] p-6">
            <h2 class="text-lg font-semibold">Tasks</h2>
            <div class="mt-4 space-y-3">
                @forelse ($tasks as $task)
                    <div class="rounded-xl border border-white/10 px-4 py-3">
                        <div class="flex gap-3 sm:items-center sm:justify-between">
                            <div>
                                <h3 class="text-sm font-semibold">{{ $task->title }}</h3>
                                <p class="mt-1 text-xs text-zinc-500">{{ $task->due_date ?: 'No due date' }}</p>
                                @if ($task->notes)<p class="mt-2 text-sm text-zinc-400">{{ $task->notes }}</p>@endif
                            </div>
                            @if (! $task->completed_at)
                                <form method="POST" action="{{ route('plugins.projects.tasks.complete', [$project->id, $task->id]) }}">
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
                    <p class="text-sm text-zinc-400">No tasks yet.</p>
                @endforelse
            </div>
        </section>
    </main>
</x-layouts.app>
