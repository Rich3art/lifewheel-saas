<x-layouts.app title="LifeWheel History">
    <main class="mx-auto max-w-5xl px-6 py-10">
        <a href="{{ route('plugins.lifewheel.index') }}" class="text-sm text-zinc-400">LifeWheel</a>
        <h1 class="mt-2 text-3xl font-semibold">Assessment history</h1>
        <p class="mt-2 text-sm text-zinc-400">{{ \Illuminate\Support\Carbon::parse($assessment->created_at)->format('Y-m-d H:i') }} / overall {{ number_format((float) $assessment->overall_score, 1) }}/10</p>

        <div class="mt-8 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($areas as $area)
                <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-5">
                    <p class="text-sm text-zinc-500">{{ $area['group'] }}</p>
                    <h2 class="mt-1 text-lg font-semibold">{{ $area['name'] }}</h2>
                    <p class="mt-4 text-3xl font-semibold">{{ $scores[$area['key']]->score ?? 0 }}/10</p>
                </div>
            @endforeach
        </div>

        @if ($assessment->reflection)
            <section class="mt-6 rounded-2xl border border-white/10 bg-white/[0.03] p-6">
                <h2 class="text-lg font-semibold">Reflection</h2>
                <p class="mt-3 whitespace-pre-line text-sm leading-6 text-zinc-300">{{ $assessment->reflection }}</p>
            </section>
        @endif
    </main>
</x-layouts.app>
