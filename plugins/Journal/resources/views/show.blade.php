<x-layouts.app title="Journal Entry">
    <main class="mx-auto max-w-5xl px-6 py-10">
        <a href="{{ route('plugins.journal.index') }}" class="text-sm text-zinc-400">Journal</a>
        <div class="mt-2 flex flex-col gap-4 border-b border-white/10 pb-8 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h1 class="text-3xl font-semibold">{{ $entry->title ?: 'Untitled entry' }}</h1>
                <p class="mt-2 text-sm text-zinc-500">{{ \Illuminate\Support\Carbon::parse($entry->entry_date)->format('Y-m-d') }}</p>
            </div>
            <form method="POST" action="{{ route('plugins.journal.entries.destroy', $entry->id) }}">
                @csrf
                @method('DELETE')
                <button class="rounded-xl border border-red-400/30 px-4 py-2 text-sm text-red-200">Delete</button>
            </form>
        </div>

        @if (session('status') === 'journal-entry-created')
            <div class="mt-6 rounded-xl border border-emerald-400/30 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-100">Entry saved.</div>
        @endif
        @if (session('status') === 'journal-entry-updated')
            <div class="mt-6 rounded-xl border border-emerald-400/30 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-100">Entry updated.</div>
        @endif

        <article class="mt-8 rounded-2xl border border-white/10 bg-white/[0.03] p-6">
            <div class="flex flex-wrap gap-3 text-sm text-zinc-400">
                @if ($entry->mood)<span>Mood {{ $entry->mood }}/10</span>@endif
                @if ($entry->energy)<span>Energy {{ $entry->energy }}/10</span>@endif
                @foreach ($entry->areas as $area)
                    <span>{{ $areas[$area] ?? $area }}</span>
                @endforeach
            </div>
            <p class="mt-6 whitespace-pre-line leading-7 text-zinc-200">{{ $entry->body }}</p>
        </article>

        <section class="mt-6 rounded-2xl border border-white/10 bg-white/[0.03] p-6">
            <h2 class="text-lg font-semibold">Edit entry</h2>
            <form method="POST" action="{{ route('plugins.journal.entries.update', $entry->id) }}" class="mt-6 space-y-4">
                @csrf
                @method('PUT')
                <input name="title" value="{{ old('title', $entry->title) }}" placeholder="Title" class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                <textarea name="body" rows="9" required class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">{{ old('body', $entry->body) }}</textarea>
                <div class="grid gap-3 sm:grid-cols-3">
                    <input name="entry_date" type="date" required value="{{ old('entry_date', $entry->entry_date) }}" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                    <input name="mood" type="number" min="1" max="10" value="{{ old('mood', $entry->mood) }}" placeholder="Mood 1-10" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                    <input name="energy" type="number" min="1" max="10" value="{{ old('energy', $entry->energy) }}" placeholder="Energy 1-10" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                </div>
                <div class="grid gap-2 sm:grid-cols-3">
                    @foreach ($areas as $key => $label)
                        <label class="flex items-center gap-2 rounded-xl border border-white/10 px-3 py-2 text-sm text-zinc-300">
                            <input type="checkbox" name="areas[]" value="{{ $key }}" @checked(in_array($key, old('areas', $entry->areas), true))>
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
                <button class="rounded-xl bg-white px-4 py-3 text-sm font-semibold text-zinc-950">Update entry</button>
            </form>
        </section>
    </main>
</x-layouts.app>
