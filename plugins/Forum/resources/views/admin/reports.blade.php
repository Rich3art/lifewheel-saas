<x-layouts.app title="Forum Moderation">
    <main class="mx-auto max-w-7xl px-6 py-10">
        <div class="border-b border-white/10 pb-8">
            <p class="text-sm text-zinc-400">Super Admin</p>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight">Forum moderation</h1>
        </div>

        <section class="mt-8 rounded-2xl border border-white/10 bg-white/[0.03] p-6">
            <h2 class="text-lg font-semibold">Reports</h2>
            <div class="mt-5 space-y-3">
                @forelse ($reports as $report)
                    <article class="rounded-xl border border-white/10 p-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <p class="font-semibold">{{ $report->reportable_type }} #{{ $report->reportable_id }}</p>
                                <p class="mt-2 text-sm text-zinc-400">{{ $report->reason }}</p>
                                <p class="mt-1 text-xs text-zinc-500">Reported by {{ $report->reporter_name }}</p>
                            </div>
                            <form method="POST" action="{{ route('plugins.forum.admin.reports.update', $report->id) }}" class="flex gap-2">
                                @csrf
                                @method('PUT')
                                <select name="status" class="rounded-xl border border-white/10 bg-zinc-900 px-3 py-2 text-sm">
                                    @foreach (['open', 'reviewing', 'resolved', 'dismissed'] as $status)
                                        <option value="{{ $status }}" @selected($report->status === $status)>{{ ucfirst($status) }}</option>
                                    @endforeach
                                </select>
                                <button class="rounded-xl bg-white px-3 py-2 text-sm font-semibold text-zinc-950">Update</button>
                            </form>
                        </div>
                    </article>
                @empty
                    <p class="text-sm text-zinc-400">No reports yet.</p>
                @endforelse
            </div>
            <div class="mt-6">{{ $reports->links() }}</div>
        </section>
    </main>
</x-layouts.app>
