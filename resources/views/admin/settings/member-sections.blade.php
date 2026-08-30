<x-layouts.app title="Member settings visibility">
    <main class="mx-auto max-w-5xl px-6 py-10">
        <a href="{{ route('admin.dashboard') }}" class="text-sm text-zinc-400">Admin</a>
        <h1 class="mt-2 text-3xl font-semibold">Member settings visibility</h1>
        <form method="POST" action="{{ route('admin.member-settings.update') }}" class="mt-8 space-y-3">
            @csrf
            @method('PUT')
            @foreach ($sections as $section)
                <div class="grid gap-4 rounded-2xl border border-white/10 bg-white/[0.03] p-4 sm:grid-cols-[1fr_120px_120px] sm:items-center">
                    <div>
                        <h2 class="text-base font-semibold">{{ $section->label }}</h2>
                        <p class="mt-1 text-sm text-zinc-400">{{ $section->description }}</p>
                        <p class="mt-1 text-xs text-zinc-500">{{ $section->source }} / {{ $section->key }}</p>
                    </div>
                    <label class="flex items-center gap-2 text-sm text-zinc-300">
                        <input type="checkbox" name="sections[{{ $section->id }}][enabled]" value="1" @checked($section->enabled) @disabled($section->required)>
                        Visible
                    </label>
                    <input type="number" min="0" name="sections[{{ $section->id }}][sort_order]" value="{{ $section->sort_order }}" class="rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm">
                </div>
            @endforeach
            <button class="rounded-xl bg-white px-4 py-3 text-sm font-semibold text-zinc-950">Save visibility</button>
        </form>
    </main>
</x-layouts.app>
