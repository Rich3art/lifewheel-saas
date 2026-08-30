<x-layouts.app title="Users">
    <main class="mx-auto max-w-6xl px-6 py-10">
        <div class="flex flex-col gap-4 border-b border-white/10 pb-8 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="{{ route('admin.dashboard') }}" class="text-sm text-zinc-400">Admin</a>
                <h1 class="mt-2 text-3xl font-semibold">Users</h1>
            </div>
            <form method="GET" action="{{ route('admin.users.index') }}" class="flex gap-2">
                <input name="search" value="{{ $search }}" placeholder="Search users" class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm outline-none ring-emerald-300/40 focus:ring sm:w-64">
                <button class="rounded-xl bg-white px-4 py-2 text-sm font-semibold text-zinc-950">Search</button>
            </form>
        </div>

        <div class="mt-8 overflow-hidden rounded-2xl border border-white/10">
            <table class="w-full min-w-[760px] text-left text-sm">
                <thead class="bg-white/[0.04] text-zinc-400">
                    <tr>
                        <th class="px-4 py-3 font-medium">User</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium">Roles</th>
                        <th class="px-4 py-3 font-medium">Registered</th>
                        <th class="px-4 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/10">
                    @foreach ($users as $user)
                        <tr>
                            <td class="px-4 py-4">
                                <div class="font-medium text-white">{{ $user->name }}</div>
                                <div class="text-zinc-400">{{ $user->email }}</div>
                            </td>
                            <td class="px-4 py-4">
                                @if ($user->isSuspended())
                                    <span class="rounded-full bg-red-400/10 px-3 py-1 text-xs text-red-200">Suspended</span>
                                @elseif (! $user->hasVerifiedEmail())
                                    <span class="rounded-full bg-amber-400/10 px-3 py-1 text-xs text-amber-200">Unverified</span>
                                @else
                                    <span class="rounded-full bg-emerald-400/10 px-3 py-1 text-xs text-emerald-200">Active</span>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-zinc-300">{{ $user->roles->pluck('name')->join(', ') ?: 'None' }}</td>
                            <td class="px-4 py-4 text-zinc-400">{{ $user->created_at?->format('Y-m-d') }}</td>
                            <td class="px-4 py-4 text-right">
                                <a href="{{ route('admin.users.edit', $user) }}" class="text-white underline">Manage</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">{{ $users->links() }}</div>
    </main>
</x-layouts.app>
