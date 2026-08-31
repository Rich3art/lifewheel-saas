<x-layouts.app title="LifeWheel">
    @php
        $scoreValues = collect($areas)->map(fn ($area) => (int) ($scores[$area['key']]->score ?? 0));
        $points = $scoreValues->map(function (int $score, int $index) use ($areas): string {
            $angle = -90 + ($index * (360 / count($areas)));
            $radius = 92 * ($score / 10);
            $x = 110 + ($radius * cos(deg2rad($angle)));
            $y = 110 + ($radius * sin(deg2rad($angle)));

            return round($x, 2).','.round($y, 2);
        })->implode(' ');
        $ranked = collect($areas)->map(fn ($area) => [
            'name' => $area['name'],
            'group' => $area['group'],
            'score' => (int) ($scores[$area['key']]->score ?? 0),
            'previous' => (int) ($previousScores[$area['key']]->score ?? 0),
        ])->sortBy('score');
    @endphp

    <main class="mx-auto max-w-7xl px-6 py-10">
        <div class="flex flex-col gap-4 border-b border-white/10 pb-8 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-sm text-zinc-400">Personal operating system</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight">LifeWheel</h1>
            </div>
            <a href="{{ route('member.dashboard') }}" class="rounded-xl border border-white/10 px-4 py-2 text-center text-sm text-zinc-200">Dashboard</a>
        </div>

        @if (session('status') === 'lifewheel-assessment-created')
            <div class="mt-6 rounded-xl border border-emerald-400/30 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-100">Assessment saved.</div>
        @endif

        <div class="mt-8 grid gap-6 xl:grid-cols-[1fr_420px]">
            <section class="rounded-2xl border border-white/10 bg-white/[0.03] p-6">
                <div class="flex flex-col gap-6 lg:flex-row lg:items-center">
                    <div class="mx-auto aspect-square w-full max-w-sm">
                        <svg viewBox="0 0 220 220" role="img" aria-label="LifeWheel chart" class="h-full w-full">
                            @foreach ([2, 4, 6, 8, 10] as $ring)
                                <circle cx="110" cy="110" r="{{ 92 * ($ring / 10) }}" fill="none" stroke="rgba(255,255,255,0.12)" stroke-width="1" />
                            @endforeach
                            @foreach ($areas as $index => $area)
                                @php
                                    $angle = -90 + ($index * (360 / count($areas)));
                                    $x = 110 + (96 * cos(deg2rad($angle)));
                                    $y = 110 + (96 * sin(deg2rad($angle)));
                                    $labelX = 110 + (108 * cos(deg2rad($angle)));
                                    $labelY = 110 + (108 * sin(deg2rad($angle)));
                                @endphp
                                <line x1="110" y1="110" x2="{{ $x }}" y2="{{ $y }}" stroke="rgba(255,255,255,0.12)" stroke-width="1" />
                                <text x="{{ $labelX }}" y="{{ $labelY }}" fill="rgb(212,212,216)" font-size="7" text-anchor="middle">{{ $area['name'] }}</text>
                            @endforeach
                            @if ($latest)
                                <polygon points="{{ $points }}" fill="rgba(52,211,153,0.24)" stroke="rgb(52,211,153)" stroke-width="2" />
                            @endif
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm text-zinc-400">Overall score</p>
                        <div class="mt-2 text-6xl font-semibold">{{ $latest ? number_format((float) $latest->overall_score, 1) : '0.0' }}</div>
                        <p class="mt-4 text-sm text-zinc-400">
                            @if ($latest)
                                Last updated {{ \Illuminate\Support\Carbon::parse($latest->created_at)->format('Y-m-d H:i') }}.
                            @else
                                Complete your first assessment to create your baseline.
                            @endif
                        </p>
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-white/10 bg-white/[0.03] p-6">
                <h2 class="text-lg font-semibold">Weakest to strongest</h2>
                <div class="mt-4 space-y-3">
                    @foreach ($ranked as $area)
                        <div class="flex items-center justify-between rounded-xl border border-white/10 px-4 py-3 text-sm">
                            <div>
                                <div class="font-medium text-zinc-100">{{ $area['name'] }}</div>
                                <div class="text-zinc-500">{{ $area['group'] }}</div>
                            </div>
                            <div class="text-right">
                                <div class="font-semibold">{{ $area['score'] }}/10</div>
                                @if ($area['previous'])
                                    <div class="text-xs {{ $area['score'] >= $area['previous'] ? 'text-emerald-300' : 'text-amber-300' }}">{{ $area['score'] - $area['previous'] >= 0 ? '+' : '' }}{{ $area['score'] - $area['previous'] }}</div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>

        <section class="mt-6 rounded-2xl border border-white/10 bg-white/[0.03] p-6">
            <h2 class="text-lg font-semibold">Update LifeWheel</h2>
            <form method="POST" action="{{ route('plugins.lifewheel.assessments.store') }}" class="mt-6 space-y-6">
                @csrf
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($areas as $area)
                        <label class="block rounded-xl border border-white/10 bg-white/[0.02] p-4">
                            <span class="flex items-center justify-between text-sm">
                                <span>{{ $area['name'] }}</span>
                                <span class="text-zinc-500">{{ $area['group'] }}</span>
                            </span>
                            <input name="scores[{{ $area['key'] }}]" type="number" min="1" max="10" required value="{{ old('scores.'.$area['key'], $scores[$area['key']]->score ?? 5) }}" class="mt-3 w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                        </label>
                    @endforeach
                </div>
                <textarea name="reflection" rows="4" placeholder="What changed since your last check-in?" class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">{{ old('reflection') }}</textarea>
                <button class="rounded-xl bg-white px-4 py-3 text-sm font-semibold text-zinc-950">Save assessment</button>
            </form>
        </section>

        <section class="mt-6 rounded-2xl border border-white/10 bg-white/[0.03] p-6">
            <h2 class="text-lg font-semibold">History</h2>
            <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                @foreach ($history as $item)
                    <a href="{{ route('plugins.lifewheel.history.show', $item->id) }}" class="rounded-xl border border-white/10 px-4 py-3 text-sm transition hover:bg-white/[0.06]">
                        <span class="block font-semibold">{{ number_format((float) $item->overall_score, 1) }}/10</span>
                        <span class="mt-1 block text-zinc-500">{{ \Illuminate\Support\Carbon::parse($item->created_at)->format('Y-m-d H:i') }}</span>
                    </a>
                @endforeach
            </div>
        </section>
    </main>
</x-layouts.app>
