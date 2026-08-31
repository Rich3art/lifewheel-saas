<x-layouts.app title="AI Life Analysis">
    <main class="mx-auto max-w-6xl px-6 py-10">
        <div class="flex flex-col gap-4 border-b border-white/10 pb-8 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm text-zinc-400">Executive intelligence</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight">AI Life Analysis</h1>
            </div>
            <a href="{{ route('member.dashboard') }}" class="rounded-xl border border-white/10 px-4 py-2 text-center text-sm text-zinc-200">Dashboard</a>
        </div>

        <section class="mt-8 rounded-2xl border border-white/10 bg-white/[0.03] p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold">Generate analysis</h2>
                    <p class="mt-2 text-sm text-zinc-400">Uses recent LifeWheel scores and reflections to produce a structured executive analysis.</p>
                </div>
                <form method="POST" action="{{ route('plugins.ai-life-analysis.analyses.store') }}">
                    @csrf
                    <button class="rounded-xl bg-white px-4 py-3 text-sm font-semibold text-zinc-950">Run analysis</button>
                </form>
            </div>
        </section>

        @if ($latest)
            <section class="mt-6 rounded-2xl border border-white/10 bg-white/[0.03] p-6">
                <h2 class="text-lg font-semibold">Latest analysis</h2>
                <p class="mt-2 text-sm text-zinc-400">{{ \Illuminate\Support\Carbon::parse($latest->created_at)->format('Y-m-d H:i') }} / {{ $latest->provider_key }} / {{ $latest->model }}</p>
                <a href="{{ route('plugins.ai-life-analysis.show', $latest->id) }}" class="mt-5 inline-flex rounded-xl border border-white/10 px-4 py-3 text-sm text-zinc-200">Open latest</a>
            </section>
        @endif

        <section class="mt-6 rounded-2xl border border-white/10 bg-white/[0.03] p-6">
            <h2 class="text-lg font-semibold">History</h2>
            <div class="mt-4 space-y-3">
                @forelse ($analyses as $analysis)
                    <a href="{{ route('plugins.ai-life-analysis.show', $analysis->id) }}" class="flex flex-col gap-2 rounded-xl border border-white/10 px-4 py-3 text-sm transition hover:bg-white/[0.06] sm:flex-row sm:items-center sm:justify-between">
                        <span>{{ \Illuminate\Support\Carbon::parse($analysis->created_at)->format('Y-m-d H:i') }}</span>
                        <span class="text-zinc-500">{{ $analysis->provider_key }} / {{ $analysis->model }}</span>
                    </a>
                @empty
                    <p class="text-sm text-zinc-400">No analyses yet.</p>
                @endforelse
            </div>
            <div class="mt-6">{{ $analyses->links() }}</div>
        </section>
    </main>
</x-layouts.app>
