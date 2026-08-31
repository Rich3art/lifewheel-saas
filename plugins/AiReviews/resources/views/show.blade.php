<x-layouts.app title="AI Review">
    <main class="mx-auto max-w-5xl px-6 py-10">
        <div class="flex flex-col gap-4 border-b border-white/10 pb-8 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm text-zinc-400">{{ ucfirst($review->period_type) }} review</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight">{{ $review->period_start }} to {{ $review->period_end }}</h1>
                <p class="mt-2 text-sm text-zinc-500">{{ $review->provider_key }} / {{ $review->model }}</p>
            </div>
            <a href="{{ route('plugins.ai-reviews.index') }}" class="rounded-xl border border-white/10 px-4 py-2 text-center text-sm text-zinc-200">All reviews</a>
        </div>

        <section class="mt-8 rounded-2xl border border-white/10 bg-white/[0.03] p-6">
            <h2 class="text-lg font-semibold">Executive summary</h2>
            <p class="mt-3 text-sm leading-6 text-zinc-300">{{ $review->structured['executive_summary'] ?? $review->content }}</p>
        </section>

        <section class="mt-6 grid gap-4 md:grid-cols-2">
            @foreach ([
                'wins' => 'Wins',
                'misses' => 'Misses',
                'patterns' => 'Patterns',
                'risks' => 'Risks',
                'opportunities' => 'Opportunities',
                'next_period_focus' => 'Next period focus',
                'recommended_actions' => 'Recommended actions',
                'what_to_stop' => 'What to stop',
                'reflection_questions' => 'Reflection questions',
            ] as $key => $label)
                <article class="rounded-2xl border border-white/10 bg-white/[0.03] p-5">
                    <h2 class="text-base font-semibold">{{ $label }}</h2>
                    <ul class="mt-3 list-inside list-disc space-y-2 text-sm text-zinc-300">
                        @forelse (($review->structured[$key] ?? []) as $item)
                            <li>{{ $item }}</li>
                        @empty
                            <li>No items returned.</li>
                        @endforelse
                    </ul>
                </article>
            @endforeach
        </section>
    </main>
</x-layouts.app>
