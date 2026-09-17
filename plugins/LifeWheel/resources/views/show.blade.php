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
                    @if (! empty($scores[$area['key']]->note))
                        <p class="mt-4 whitespace-pre-line text-sm leading-6 text-zinc-300">{{ $scores[$area['key']]->note }}</p>
                    @else
                        <p class="mt-4 text-sm leading-6 text-amber-200">No category note was added. Add a sentence next time so the AI feedback can be more personal.</p>
                    @endif
                </div>
            @endforeach
        </div>

        @if ($assessment->reflection)
            <section class="mt-6 rounded-2xl border border-white/10 bg-white/[0.03] p-6">
                <h2 class="text-lg font-semibold">Reflection</h2>
                <p class="mt-3 whitespace-pre-line text-sm leading-6 text-zinc-300">{{ $assessment->reflection }}</p>
            </section>
        @endif

        @if ($report)
            <section class="mt-6 rounded-2xl border border-emerald-400/20 bg-emerald-400/10 p-6">
                <p class="text-sm text-emerald-200">AI Coach report saved with this LifeWheel</p>
                <h2 class="mt-1 text-2xl font-semibold">Past, present, and future feedback</h2>
                <p class="mt-4 text-sm leading-6 text-emerald-50/90">{{ $report['summary'] ?? '' }}</p>

                <div class="mt-6 grid gap-4">
                    @foreach (($report['category_feedback'] ?? []) as $item)
                        <article class="rounded-xl border border-emerald-200/15 bg-zinc-950/40 p-5">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <p class="text-xs uppercase tracking-[0.16em] text-emerald-200/70">{{ $item['group'] ?? '' }}</p>
                                    <h3 class="mt-1 text-lg font-semibold">{{ $item['area_name'] ?? '' }}</h3>
                                </div>
                                <div class="text-sm text-emerald-50/80">
                                    {{ $item['score'] ?? 0 }}/10
                                    @if (($item['previous_score'] ?? null) !== null)
                                        <span class="text-emerald-200">({{ ($item['change'] ?? 0) >= 0 ? '+' : '' }}{{ $item['change'] ?? 0 }})</span>
                                    @endif
                                </div>
                            </div>
                            <p class="mt-4 text-sm leading-6 text-zinc-100">{{ $item['feedback'] ?? '' }}</p>
                            <p class="mt-3 rounded-xl border border-white/10 bg-white/[0.04] px-4 py-3 text-sm text-zinc-200">{{ $item['next_step'] ?? '' }}</p>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif
    </main>
</x-layouts.app>
