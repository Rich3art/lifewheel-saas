<x-layouts.app title="Privacy requests">
    <main class="mx-auto max-w-6xl px-6 py-10">
        <a href="{{ route('admin.dashboard') }}" class="text-sm text-zinc-400">Admin</a>
        <h1 class="mt-2 text-3xl font-semibold">Privacy requests</h1>
        <div class="mt-8 space-y-4">
            @forelse ($requests as $privacyRequest)
                <article class="rounded-2xl border border-white/10 bg-white/[0.03] p-5">
                    <div class="grid gap-4 lg:grid-cols-[1fr_260px]">
                        <div>
                            <h2 class="text-lg font-semibold">{{ ucfirst(str_replace('_', ' ', $privacyRequest->type)) }}</h2>
                            <p class="mt-1 text-sm text-zinc-400">{{ $privacyRequest->user->name }} / {{ $privacyRequest->user->email }}</p>
                            <p class="mt-3 text-sm text-zinc-300">{{ $privacyRequest->details ?: 'No details supplied.' }}</p>
                        </div>
                        <form method="POST" action="{{ route('admin.privacy-requests.update', $privacyRequest) }}" class="space-y-3">
                            @csrf
                            @method('PUT')
                            <select name="status" class="w-full rounded-xl border border-white/10 bg-zinc-900 px-4 py-3 text-sm">
                                @foreach (['pending', 'identity_check', 'processing', 'completed', 'rejected', 'cancelled'] as $status)
                                    <option value="{{ $status }}" @selected($privacyRequest->status === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                                @endforeach
                            </select>
                            <textarea name="admin_notes" rows="3" placeholder="Internal notes" class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">{{ $privacyRequest->admin_notes }}</textarea>
                            <button class="w-full rounded-xl bg-white px-4 py-3 text-sm font-semibold text-zinc-950">Update</button>
                        </form>
                    </div>
                </article>
            @empty
                <p class="rounded-2xl border border-white/10 bg-white/[0.03] p-6 text-sm text-zinc-400">No privacy requests yet.</p>
            @endforelse
        </div>
        <div class="mt-6">{{ $requests->links() }}</div>
    </main>
</x-layouts.app>
