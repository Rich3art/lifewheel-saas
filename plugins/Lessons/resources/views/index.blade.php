<x-layouts.app title="Lessons">
    <main class="mx-auto max-w-7xl px-6 py-10">
        <div class="flex flex-col gap-4 border-b border-white/10 pb-8 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-sm text-zinc-400">Lessons ledger</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight">Lessons</h1>
            </div>
            <a href="{{ route('member.dashboard') }}" class="rounded-xl border border-white/10 px-4 py-2 text-center text-sm text-zinc-200">Dashboard</a>
        </div>

        <div class="mt-8 grid gap-6 xl:grid-cols-[420px_1fr]">
            <section class="rounded-2xl border border-white/10 bg-white/[0.03] p-6">
                <h2 class="text-lg font-semibold">Capture lesson</h2>
                <form method="POST" action="{{ route('plugins.lessons.lessons.store') }}" class="mt-6 space-y-4">
                    @csrf
                    <input name="title" required value="{{ old('title') }}" placeholder="Lesson title" class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                    <textarea name="body" rows="8" required placeholder="What did you learn?" class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">{{ old('body') }}</textarea>
                    <input name="learned_on" type="date" required value="{{ old('learned_on', now()->toDateString()) }}" class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                    <div class="grid gap-2 sm:grid-cols-3">
                        @foreach ($areas as $key => $label)
                            <label class="flex items-center gap-2 rounded-xl border border-white/10 px-3 py-2 text-sm text-zinc-300">
                                <input type="checkbox" name="areas[]" value="{{ $key }}">
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                    <button class="rounded-xl bg-white px-4 py-3 text-sm font-semibold text-zinc-950">Save lesson</button>
                </form>
            </section>

            <section class="rounded-2xl border border-white/10 bg-white/[0.03] p-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <h2 class="text-lg font-semibold">Ledger</h2>
                    @if ($canSearch)
                        <form method="GET" action="{{ route('plugins.lessons.index') }}" class="flex gap-2">
                            <input name="search" value="{{ $search }}" placeholder="Search lessons" class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm">
                            <button class="rounded-xl border border-white/10 px-4 py-2 text-sm">Search</button>
                        </form>
                    @endif
                </div>
                <div class="mt-6 space-y-3">
                    @forelse ($lessons as $lesson)
                        @php $lessonAreas = json_decode((string) $lesson->areas, true) ?: []; @endphp
                        <a href="{{ route('plugins.lessons.show', $lesson->id) }}" class="block rounded-xl border border-white/10 px-4 py-4 transition hover:bg-white/[0.06]">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <h3 class="font-semibold">{{ $lesson->title }}</h3>
                                    <p class="mt-2 line-clamp-2 text-sm leading-6 text-zinc-400">{{ $lesson->body }}</p>
                                </div>
                                <span class="text-sm text-zinc-500">{{ $lesson->learned_on }}</span>
                            </div>
                            <div class="mt-3 flex flex-wrap gap-2 text-xs text-zinc-500">
                                <span>{{ ucfirst($lesson->source_type) }}</span>
                                @foreach ($lessonAreas as $area)
                                    <span class="rounded-full border border-white/10 px-2 py-1">{{ $areas[$area] ?? $area }}</span>
                                @endforeach
                            </div>
                        </a>
                    @empty
                        <p class="rounded-xl border border-white/10 px-4 py-6 text-sm text-zinc-400">No lessons yet.</p>
                    @endforelse
                </div>
                <div class="mt-6">{{ $lessons->links() }}</div>
            </section>
        </div>
    </main>
</x-layouts.app>
