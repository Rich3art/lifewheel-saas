<x-layouts.app title="Member Dashboard">
    @php
        $latestWheel = \Illuminate\Support\Facades\Schema::hasTable('lifewheel_assessments')
            ? \Illuminate\Support\Facades\DB::table('lifewheel_assessments')->where('user_id', auth()->id())->latest()->first()
            : null;
        $wheelCount = \Illuminate\Support\Facades\Schema::hasTable('lifewheel_assessments')
            ? \Illuminate\Support\Facades\DB::table('lifewheel_assessments')->where('user_id', auth()->id())->count()
            : 0;
        $lifeWheelRoute = \Illuminate\Support\Facades\Route::has('plugins.lifewheel.index') ? route('plugins.lifewheel.index') : null;
        $analysisRoute = \Illuminate\Support\Facades\Route::has('plugins.ai-life-analysis.index') ? route('plugins.ai-life-analysis.index') : null;
        $coachRoute = \Illuminate\Support\Facades\Route::has('plugins.ai-coach.index') ? route('plugins.ai-coach.index') : null;
    @endphp

    <main class="mx-auto max-w-7xl px-6 py-8">
        <div class="flex flex-col gap-4 border-b border-white/10 pb-8 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm uppercase tracking-[0.18em] text-emerald-300">Personal operating system</p>
                <h1 class="mt-3 text-4xl font-semibold tracking-tight">Run your life like a CEO.</h1>
                <p class="mt-4 max-w-3xl text-sm leading-6 text-zinc-300">Start by creating a Life Wheel. Each saved wheel becomes part of your history so AI can compare where you were, where you are now, and what to focus on next.</p>
            </div>
            <a href="{{ route('member.settings') }}" class="rounded-xl border border-white/10 px-4 py-3 text-center text-sm text-zinc-200 transition hover:bg-white/[0.06]">Settings</a>
        </div>

        <section class="mt-8 grid gap-4 lg:grid-cols-3">
            <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-6">
                <p class="text-sm text-zinc-400">Current Life Score</p>
                <div class="mt-3 text-5xl font-semibold">{{ $latestWheel ? number_format((float) $latestWheel->overall_score, 1) : '0.0' }}</div>
                <p class="mt-3 text-sm text-zinc-500">{{ $wheelCount }} saved {{ \Illuminate\Support\Str::plural('wheel', $wheelCount) }}</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-6">
                <p class="text-sm text-zinc-400">Latest Check-in</p>
                <div class="mt-3 text-2xl font-semibold">{{ $latestWheel ? \Illuminate\Support\Carbon::parse($latestWheel->created_at)->format('M j, Y') : 'Not started' }}</div>
                <p class="mt-3 text-sm text-zinc-500">{{ $latestWheel ? 'Open LifeWheel to update your scores.' : 'Create your baseline wheel first.' }}</p>
            </div>
            <div class="rounded-2xl border border-emerald-400/20 bg-emerald-400/10 p-6">
                <p class="text-sm text-emerald-200">AI-ready history</p>
                <div class="mt-3 text-2xl font-semibold">Present vs past feedback</div>
                <p class="mt-3 text-sm text-emerald-100/80">After you save wheels, AI Analysis and AI Coach can use that history for encouragement and next steps.</p>
            </div>
        </section>

        <section class="mt-6 grid gap-4 lg:grid-cols-3">
            <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-6">
                <h2 class="text-lg font-semibold">1. Create your Life Wheel</h2>
                <p class="mt-3 text-sm leading-6 text-zinc-400">Rate each life category from 1 to 10, just like your notebook example. Save it as today’s baseline.</p>
                @if ($lifeWheelRoute)
                    <a href="{{ $lifeWheelRoute }}" class="mt-5 inline-flex rounded-xl bg-white px-4 py-3 text-sm font-semibold text-zinc-950">{{ $latestWheel ? 'Update Life Wheel' : 'Create Life Wheel' }}</a>
                @else
                    <p class="mt-5 rounded-xl border border-amber-300/30 bg-amber-300/10 px-4 py-3 text-sm text-amber-100">LifeWheel plugin is not active yet.</p>
                @endif
            </div>

            <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-6">
                <h2 class="text-lg font-semibold">2. Generate AI analysis</h2>
                <p class="mt-3 text-sm leading-6 text-zinc-400">AI reads saved wheels and reflections to summarize strengths, risks, opportunities, and measurable next actions.</p>
                @if ($analysisRoute)
                    <a href="{{ $analysisRoute }}" class="mt-5 inline-flex rounded-xl border border-white/10 px-4 py-3 text-sm text-zinc-200 transition hover:bg-white/[0.06]">Open AI Analysis</a>
                @else
                    <p class="mt-5 rounded-xl border border-amber-300/30 bg-amber-300/10 px-4 py-3 text-sm text-amber-100">AI Life Analysis plugin is not active yet.</p>
                @endif
            </div>

            <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-6">
                <h2 class="text-lg font-semibold">3. Ask your life</h2>
                <p class="mt-3 text-sm leading-6 text-zinc-400">Ask questions like “What improved since my last wheel?” or “Where should I focus this month?”</p>
                @if ($coachRoute)
                    <a href="{{ $coachRoute }}" class="mt-5 inline-flex rounded-xl border border-white/10 px-4 py-3 text-sm text-zinc-200 transition hover:bg-white/[0.06]">Open AI Coach</a>
                @else
                    <p class="mt-5 rounded-xl border border-amber-300/30 bg-amber-300/10 px-4 py-3 text-sm text-amber-100">AI Coach plugin is not active yet.</p>
                @endif
            </div>
        </section>
    </main>
</x-layouts.app>
