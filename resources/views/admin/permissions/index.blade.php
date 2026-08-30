<x-layouts.app title="Permissions">
    <main class="mx-auto max-w-5xl px-6 py-10">
        <a href="{{ route('admin.dashboard') }}" class="text-sm text-zinc-400">Admin</a>
        <h1 class="mt-2 text-3xl font-semibold">Permissions</h1>

        <section class="mt-8 rounded-2xl border border-white/10 bg-white/[0.03] p-6">
            <h2 class="text-lg font-semibold">Create permission</h2>
            <form method="POST" action="{{ route('admin.permissions.store') }}" class="mt-5 grid gap-4 lg:grid-cols-[1fr_1fr_auto]">
                @csrf
                <input name="name" placeholder="Permission name" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm outline-none ring-emerald-300/40 focus:ring">
                <input name="slug" placeholder="permission.slug" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm outline-none ring-emerald-300/40 focus:ring">
                <button class="rounded-xl bg-white px-4 py-3 text-sm font-semibold text-zinc-950">Create</button>
            </form>
        </section>

        <div class="mt-8 overflow-hidden rounded-2xl border border-white/10">
            <table class="w-full min-w-[640px] text-left text-sm">
                <thead class="bg-white/[0.04] text-zinc-400">
                    <tr>
                        <th class="px-4 py-3 font-medium">Name</th>
                        <th class="px-4 py-3 font-medium">Slug</th>
                        <th class="px-4 py-3 font-medium">System</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/10">
                    @foreach ($permissions as $permission)
                        <tr>
                            <td class="px-4 py-4 text-white">{{ $permission->name }}</td>
                            <td class="px-4 py-4 text-zinc-400">{{ $permission->slug }}</td>
                            <td class="px-4 py-4 text-zinc-400">{{ $permission->is_system ? 'Yes' : 'No' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-6">{{ $permissions->links() }}</div>
    </main>
</x-layouts.app>
