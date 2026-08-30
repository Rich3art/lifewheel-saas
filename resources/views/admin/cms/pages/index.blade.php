<x-layouts.app title="Pages">
    <main class="mx-auto max-w-6xl px-6 py-10">
        <div class="flex items-center justify-between border-b border-white/10 pb-8">
            <div>
                <a href="{{ route('admin.dashboard') }}" class="text-sm text-zinc-400">Admin</a>
                <h1 class="mt-2 text-3xl font-semibold">Pages</h1>
            </div>
            <a href="{{ route('admin.pages.create') }}" class="rounded-xl bg-white px-4 py-3 text-sm font-semibold text-zinc-950">Create page</a>
        </div>
        <div class="mt-8 overflow-hidden rounded-2xl border border-white/10">
            <table class="w-full min-w-[760px] text-left text-sm">
                <thead class="bg-white/[0.04] text-zinc-400"><tr><th class="px-4 py-3">Title</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Legal</th><th class="px-4 py-3"></th></tr></thead>
                <tbody class="divide-y divide-white/10">
                    @foreach ($pages as $page)
                        <tr>
                            <td class="px-4 py-4"><div class="font-medium text-white">{{ $page->title }}</div><div class="text-zinc-400">/{{ $page->slug }}</div></td>
                            <td class="px-4 py-4 text-zinc-300">{{ $page->status }}</td>
                            <td class="px-4 py-4 text-zinc-300">{{ $page->is_legal ? 'Yes' : 'No' }}</td>
                            <td class="px-4 py-4 text-right"><a href="{{ route('admin.pages.edit', $page) }}" class="text-white underline">Edit</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-6">{{ $pages->links() }}</div>
    </main>
</x-layouts.app>
