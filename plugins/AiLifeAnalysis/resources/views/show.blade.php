<x-layouts.app title="AI Life Analysis">
    <main class="mx-auto max-w-5xl px-6 py-10">
        <a href="{{ route('plugins.ai-life-analysis.index') }}" class="text-sm text-zinc-400">AI Life Analysis</a>
        <h1 class="mt-2 text-3xl font-semibold">Structured analysis</h1>
        <p class="mt-2 text-sm text-zinc-500">{{ \Illuminate\Support\Carbon::parse($analysis->created_at)->format('Y-m-d H:i') }} / {{ $analysis->provider_key }} / {{ $analysis->model }}</p>

        @if ($analysis->structured)
            <section class="mt-8 rounded-2xl border border-white/10 bg-white/[0.03] p-6">
                <h2 class="text-lg font-semibold">Overall balance</h2>
                <p class="mt-3 text-sm leading-6 text-zinc-300">{{ $analysis->structured['overall_balance'] ?? '' }}</p>
            </section>

            <div class="mt-6 grid gap-6 lg:grid-cols-2">
                @foreach ([
                    'strongest_areas' => 'Strongest areas',
                    'weakest_areas' => 'Weakest areas',
                    'biggest_improvements' => 'Biggest improvements',
                    'biggest_declines' => 'Biggest declines',
                    'patterns' => 'Patterns',
                    'possible_causes' => 'Possible causes',
                    'constraints' => 'Constraints',
                    'recommended_priority_areas' => 'Recommended priority areas',
                    'recommended_actions' => 'Recommended actions',
                    'what_to_avoid' => 'What to avoid',
                    'reflection_questions' => 'Reflection questions',
                ] as $key => $label)
                    <section class="rounded-2xl border border-white/10 bg-white/[0.03] p-6">
                        <h2 class="text-lg font-semibold">{{ $label }}</h2>
                        <ul class="mt-4 space-y-2 text-sm leading-6 text-zinc-300">
                            @foreach (($analysis->structured[$key] ?? []) as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    </section>
                @endforeach
            </div>

            <section class="mt-6 rounded-2xl border border-white/10 bg-white/[0.03] p-6">
                <h2 class="text-lg font-semibold">Historical comparison</h2>
                <p class="mt-3 text-sm leading-6 text-zinc-300">{{ $analysis->structured['historical_comparison'] ?? '' }}</p>
            </section>
        @else
            <section class="mt-8 rounded-2xl border border-white/10 bg-white/[0.03] p-6">
                <pre class="whitespace-pre-wrap text-sm leading-6 text-zinc-300">{{ $analysis->content }}</pre>
            </section>
        @endif
    </main>
</x-layouts.app>
