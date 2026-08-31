<x-layouts.app title="Community">
    <main class="mx-auto max-w-7xl px-6 py-10">
        <div class="flex flex-col gap-4 border-b border-white/10 pb-8 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-sm text-zinc-400">Private member community</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight">Community</h1>
            </div>
            <a href="{{ route('plugins.forum.messages.index') }}" class="rounded-xl border border-white/10 px-4 py-2 text-center text-sm text-zinc-200">Messages</a>
        </div>

        <div class="mt-8 grid gap-6 xl:grid-cols-[420px_1fr]">
            <section class="rounded-2xl border border-white/10 bg-white/[0.03] p-6">
                <h2 class="text-lg font-semibold">Create topic</h2>
                <form method="POST" action="{{ route('plugins.forum.topics.store') }}" class="mt-6 space-y-4">
                    @csrf
                    <select name="category_id" required class="w-full rounded-xl border border-white/10 bg-zinc-900 px-4 py-3 text-sm">
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                    <input name="title" required maxlength="180" value="{{ old('title') }}" placeholder="Topic title" class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                    <textarea name="body" required rows="6" placeholder="Write something useful for the community." class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">{{ old('body') }}</textarea>
                    <button class="rounded-xl bg-white px-4 py-3 text-sm font-semibold text-zinc-950">Post topic</button>
                </form>
            </section>

            <section class="rounded-2xl border border-white/10 bg-white/[0.03] p-6">
                <h2 class="text-lg font-semibold">Recent topics</h2>
                <div class="mt-6 space-y-3">
                    @forelse ($topics as $topic)
                        <a href="{{ route('plugins.forum.topics.show', $topic->id) }}" class="block rounded-xl border border-white/10 px-4 py-4 transition hover:bg-white/[0.06]">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <h3 class="font-semibold">{{ $topic->title }}</h3>
                                    <p class="mt-2 line-clamp-2 text-sm text-zinc-400">{{ $topic->body }}</p>
                                </div>
                                <span class="text-sm text-zinc-500">{{ $topic->category_name }}</span>
                            </div>
                            <p class="mt-3 text-xs text-zinc-500">By {{ $topic->author_name }} / {{ \Illuminate\Support\Carbon::parse($topic->last_activity_at)->diffForHumans() }}</p>
                        </a>
                    @empty
                        <p class="text-sm text-zinc-400">No topics yet.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </main>
</x-layouts.app>
