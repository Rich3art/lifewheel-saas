<x-layouts.app title="AI Reviews">
    <main class="mx-auto max-w-6xl px-6 py-10">
        <div class="flex flex-col gap-4 border-b border-white/10 pb-8 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm text-zinc-400">Executive review rhythm</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight">AI Reviews</h1>
            </div>
            <a href="{{ route('member.dashboard') }}" class="rounded-xl border border-white/10 px-4 py-2 text-center text-sm text-zinc-200">Dashboard</a>
        </div>

        <section class="mt-8 rounded-2xl border border-white/10 bg-white/[0.03] p-6">
            <h2 class="text-lg font-semibold">Generate a review</h2>
            <form method="POST" action="{{ route('plugins.ai-reviews.reviews.store') }}" class="mt-5 grid gap-4 md:grid-cols-4">
                @csrf
                <label class="md:col-span-2">
                    <span class="text-sm text-zinc-400">Period</span>
                    <select name="period_type" class="mt-2 w-full rounded-xl border border-white/10 bg-zinc-950/60 px-4 py-3 text-sm text-zinc-100">
                        @foreach ($periodTypes as $period)
                            <option value="{{ $period }}" @selected(old('period_type') === $period)>{{ ucfirst($period) }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span class="text-sm text-zinc-400">Custom start</span>
                    <input type="date" name="start_date" value="{{ old('start_date') }}" class="mt-2 w-full rounded-xl border border-white/10 bg-zinc-950/60 px-4 py-3 text-sm text-zinc-100">
                </label>
                <label>
                    <span class="text-sm text-zinc-400">Custom end</span>
                    <input type="date" name="end_date" value="{{ old('end_date') }}" class="mt-2 w-full rounded-xl border border-white/10 bg-zinc-950/60 px-4 py-3 text-sm text-zinc-100">
                </label>
                @error('period_type')
                    <p class="text-sm text-red-300 md:col-span-4">{{ $message }}</p>
                @enderror
                @error('start_date')
                    <p class="text-sm text-red-300 md:col-span-4">{{ $message }}</p>
                @enderror
                @error('end_date')
                    <p class="text-sm text-red-300 md:col-span-4">{{ $message }}</p>
                @enderror
                <div class="md:col-span-4">
                    <button class="rounded-xl bg-white px-4 py-3 text-sm font-semibold text-zinc-950">Run review</button>
                </div>
            </form>
        </section>

        <section class="mt-6 rounded-2xl border border-white/10 bg-white/[0.03] p-6">
            <h2 class="text-lg font-semibold">Review history</h2>
            <div class="mt-4 space-y-3">
                @forelse ($reviews as $review)
                    <a href="{{ route('plugins.ai-reviews.show', $review->id) }}" class="flex flex-col gap-2 rounded-xl border border-white/10 px-4 py-3 text-sm transition hover:bg-white/[0.06] sm:flex-row sm:items-center sm:justify-between">
                        <span>{{ ucfirst($review->period_type) }} review: {{ $review->period_start }} to {{ $review->period_end }}</span>
                        <span class="text-zinc-500">{{ $review->provider_key }} / {{ $review->model }}</span>
                    </a>
                @empty
                    <p class="text-sm text-zinc-400">No AI reviews yet.</p>
                @endforelse
            </div>
            <div class="mt-6">{{ $reviews->links() }}</div>
        </section>
    </main>
</x-layouts.app>
