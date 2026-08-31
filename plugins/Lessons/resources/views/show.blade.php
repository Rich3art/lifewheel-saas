<x-layouts.app title="Lesson">
    <main class="mx-auto max-w-5xl px-6 py-10">
        <a href="{{ route('plugins.lessons.index') }}" class="text-sm text-zinc-400">Lessons</a>
        <div class="mt-2 flex flex-col gap-4 border-b border-white/10 pb-8 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-sm text-zinc-500">{{ ucfirst($lesson->source_type) }} / {{ $lesson->learned_on }}</p>
                <h1 class="mt-2 text-3xl font-semibold">{{ $lesson->title }}</h1>
            </div>
            <form method="POST" action="{{ route('plugins.lessons.lessons.destroy', $lesson->id) }}">
                @csrf
                @method('DELETE')
                <button class="rounded-xl border border-red-400/30 px-4 py-2 text-sm text-red-200">Delete</button>
            </form>
        </div>

        <article class="mt-8 rounded-2xl border border-white/10 bg-white/[0.03] p-6">
            <div class="flex flex-wrap gap-3 text-sm text-zinc-400">
                @foreach ($lesson->areas as $area)
                    <span>{{ $areas[$area] ?? $area }}</span>
                @endforeach
            </div>
            <p class="mt-6 whitespace-pre-line leading-7 text-zinc-200">{{ $lesson->body }}</p>
        </article>

        <section class="mt-6 rounded-2xl border border-white/10 bg-white/[0.03] p-6">
            <h2 class="text-lg font-semibold">Edit lesson</h2>
            <form method="POST" action="{{ route('plugins.lessons.lessons.update', $lesson->id) }}" class="mt-6 space-y-4">
                @csrf
                @method('PUT')
                <input name="title" required value="{{ old('title', $lesson->title) }}" class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                <textarea name="body" rows="8" required class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">{{ old('body', $lesson->body) }}</textarea>
                <input name="learned_on" type="date" required value="{{ old('learned_on', $lesson->learned_on) }}" class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                <div class="grid gap-2 sm:grid-cols-3">
                    @foreach ($areas as $key => $label)
                        <label class="flex items-center gap-2 rounded-xl border border-white/10 px-3 py-2 text-sm text-zinc-300">
                            <input type="checkbox" name="areas[]" value="{{ $key }}" @checked(in_array($key, old('areas', $lesson->areas), true))>
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
                <button class="rounded-xl bg-white px-4 py-3 text-sm font-semibold text-zinc-950">Update lesson</button>
            </form>
        </section>
    </main>
</x-layouts.app>
