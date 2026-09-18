<x-layouts.app title="LifeWheel History">
    <main class="mx-auto max-w-5xl px-6 py-10">
        <a href="{{ route('plugins.lifewheel.index') }}" class="text-sm text-zinc-400">LifeWheel</a>
        <h1 class="mt-2 text-3xl font-semibold">Assessment history</h1>
        <p class="mt-2 text-sm text-zinc-400">{{ \Illuminate\Support\Carbon::parse($assessment->created_at)->format('Y-m-d H:i') }} / overall {{ number_format((float) $assessment->overall_score, 1) }}/10</p>

        @if ($assessment->reflection)
            <section class="mt-6 rounded-2xl border border-white/10 bg-white/[0.03] p-6">
                <h2 class="text-lg font-semibold">Reflection</h2>
                <p class="mt-3 whitespace-pre-line text-sm leading-6 text-zinc-300">{{ $assessment->reflection }}</p>
            </section>
        @endif

        @if ($report)
            <section class="mt-6 rounded-2xl border border-emerald-400/20 bg-emerald-400/10 p-6">
                <p class="text-sm text-emerald-200">Saved with this LifeWheel</p>
                <h2 class="mt-1 text-2xl font-semibold">AI Coach Feedback</h2>
                @if (($report['_meta']['provider_key'] ?? null) === 'openai')
                    <p class="mt-2 text-xs text-emerald-100/80">Generated with OpenAI via {{ $report['_meta']['model'] ?? 'configured model' }}.</p>
                @elseif (($report['_meta']['generated_by'] ?? null) === 'local_fallback')
                    <p class="mt-2 text-xs text-amber-100/80">
                        @if (($report['_meta']['fallback_reason'] ?? null) === 'missing_ai_coach_entitlement')
                            Generated with the local fallback because this account does not have AI Coach access.
                        @else
                            Generated with the local fallback because the AI provider was not available for this submission.
                        @endif
                    </p>
                @endif
                <p class="mt-4 text-sm leading-6 text-emerald-50/90">{{ $report['summary'] ?? '' }}</p>

                <div class="mt-6 space-y-4">
                    @foreach (($report['category_feedback'] ?? []) as $item)
                        <p class="text-sm leading-6 text-zinc-100"><span class="font-semibold text-emerald-100">{{ $item['area_name'] ?? '' }}:</span> {{ $item['feedback'] ?? '' }}</p>
                    @endforeach
                </div>
            </section>
        @endif
    </main>
</x-layouts.app>
