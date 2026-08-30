<x-layouts.app title="Blog admin">
    <main class="mx-auto max-w-6xl px-6 py-10">
        <div class="flex items-center justify-between border-b border-white/10 pb-8">
            <div>
                <a href="{{ route('admin.dashboard') }}" class="text-sm text-zinc-400">Admin</a>
                <h1 class="mt-2 text-3xl font-semibold">Blog</h1>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('admin.blog.taxonomy') }}" class="rounded-xl border border-white/10 px-4 py-3 text-sm">Taxonomy</a>
                <a href="{{ route('admin.blog.create') }}" class="rounded-xl bg-white px-4 py-3 text-sm font-semibold text-zinc-950">Create post</a>
            </div>
        </div>
        <div class="mt-8 overflow-hidden rounded-2xl border border-white/10">
            <table class="w-full min-w-[760px] text-left text-sm">
                <thead class="bg-white/[0.04] text-zinc-400"><tr><th class="px-4 py-3">Post</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Author</th><th class="px-4 py-3"></th></tr></thead>
                <tbody class="divide-y divide-white/10">
                    @foreach ($posts as $post)
                        <tr>
                            <td class="px-4 py-4"><div class="font-medium text-white">{{ $post->title }}</div><div class="text-zinc-400">/blog/{{ $post->slug }}</div></td>
                            <td class="px-4 py-4 text-zinc-300">{{ $post->status }}</td>
                            <td class="px-4 py-4 text-zinc-400">{{ $post->author?->name ?: 'Unknown' }}</td>
                            <td class="px-4 py-4 text-right"><a href="{{ route('admin.blog.edit', $post) }}" class="text-white underline">Edit</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-6">{{ $posts->links() }}</div>
    </main>
</x-layouts.app>
