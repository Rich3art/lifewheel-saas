<x-layouts.app title="Journal">
    <main class="mx-auto max-w-7xl px-6 py-10">
        <div class="flex flex-col gap-4 border-b border-white/10 pb-8 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-sm text-zinc-400">Private reflection</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight">Journal</h1>
            </div>
            <a href="{{ route('member.dashboard') }}" class="rounded-xl border border-white/10 px-4 py-2 text-center text-sm text-zinc-200">Dashboard</a>
        </div>

        @if (session('status') === 'journal-entry-deleted')
            <div class="mt-6 rounded-xl border border-emerald-400/30 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-100">Entry deleted.</div>
        @endif

        <div class="mt-8 grid gap-6 xl:grid-cols-[420px_1fr]">
            <section class="rounded-2xl border border-white/10 bg-white/[0.03] p-6">
                <h2 class="text-lg font-semibold">Capture today</h2>
                <form method="POST" action="{{ route('plugins.journal.entries.store') }}" class="mt-6 space-y-4">
                    @csrf
                    <input name="title" value="{{ old('title') }}" placeholder="Title" class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                    <textarea name="body" rows="9" required placeholder="What do you need to capture?" class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">{{ old('body') }}</textarea>
                    <div class="grid gap-3 sm:grid-cols-3">
                        <input name="entry_date" type="date" required value="{{ old('entry_date', now()->toDateString()) }}" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                        <input name="mood" type="number" min="1" max="10" value="{{ old('mood') }}" placeholder="Mood 1-10" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                        <input name="energy" type="number" min="1" max="10" value="{{ old('energy') }}" placeholder="Energy 1-10" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                    </div>
                    <div class="grid gap-2 sm:grid-cols-3">
                        @foreach ($areas as $key => $label)
                            <label class="flex items-center gap-2 rounded-xl border border-white/10 px-3 py-2 text-sm text-zinc-300">
                                <input type="checkbox" name="areas[]" value="{{ $key }}" @checked(in_array($key, old('areas', []), true))>
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                    <button class="rounded-xl bg-white px-4 py-3 text-sm font-semibold text-zinc-950">Save entry</button>
                </form>
            </section>

            <section class="rounded-2xl border border-white/10 bg-white/[0.03] p-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <h2 class="text-lg font-semibold">Timeline</h2>
                    @if ($canSearch)
                        <form method="GET" action="{{ route('plugins.journal.index') }}" class="flex gap-2">
                            <input name="search" value="{{ $search }}" placeholder="Search entries" class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm">
                            <button class="rounded-xl border border-white/10 px-4 py-2 text-sm">Search</button>
                        </form>
                    @endif
                </div>
                <div class="mt-6 space-y-3">
                    @forelse ($entries as $entry)
                        @php $entryAreas = json_decode((string) $entry->areas, true) ?: []; @endphp
                        <a href="{{ route('plugins.journal.show', $entry->id) }}" class="block rounded-xl border border-white/10 px-4 py-4 transition hover:bg-white/[0.06]">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <h3 class="font-semibold">{{ $entry->title ?: 'Untitled entry' }}</h3>
                                    <p class="mt-2 line-clamp-2 text-sm leading-6 text-zinc-400">{{ $entry->body }}</p>
                                </div>
                                <span class="text-sm text-zinc-500">{{ \Illuminate\Support\Carbon::parse($entry->entry_date)->format('Y-m-d') }}</span>
                            </div>
                            <div class="mt-3 flex flex-wrap gap-2 text-xs text-zinc-500">
                                @foreach ($entryAreas as $area)
                                    <span class="rounded-full border border-white/10 px-2 py-1">{{ $areas[$area] ?? $area }}</span>
                                @endforeach
                                @if ($entry->mood)<span>Mood {{ $entry->mood }}/10</span>@endif
                                @if ($entry->energy)<span>Energy {{ $entry->energy }}/10</span>@endif
                            </div>
                        </a>
                    @empty
                        <p class="rounded-xl border border-white/10 px-4 py-6 text-sm text-zinc-400">No journal entries yet.</p>
                    @endforelse
                </div>
                <div class="mt-6">{{ $entries->links() }}</div>
            </section>
        </div>
    </main>
</x-layouts.app>
