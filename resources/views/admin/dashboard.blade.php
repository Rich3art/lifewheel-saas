<x-layouts.app title="Super Admin dashboard">
    <main class="mx-auto max-w-6xl px-6 py-10">
        <div class="flex flex-col gap-4 border-b border-white/10 pb-8 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm text-zinc-400">Core administration</p>
                <h1 class="mt-2 text-3xl font-semibold">Super Admin dashboard</h1>
            </div>
            <a href="{{ route('member.dashboard') }}" class="rounded-xl border border-white/10 px-4 py-2 text-sm text-zinc-200">Member app</a>
        </div>

        <div class="mt-8 grid gap-4 sm:grid-cols-3">
            @can('admin.users.manage')
                <a href="{{ route('admin.users.index') }}" class="rounded-2xl border border-white/10 bg-white/[0.03] p-5 transition hover:bg-white/[0.06]">
                    <span class="text-sm text-zinc-400">Users</span>
                    <strong class="mt-3 block text-xl">Manage accounts</strong>
                </a>
            @endcan
            @can('admin.roles.manage')
                <a href="{{ route('admin.roles.index') }}" class="rounded-2xl border border-white/10 bg-white/[0.03] p-5 transition hover:bg-white/[0.06]">
                    <span class="text-sm text-zinc-400">Roles</span>
                    <strong class="mt-3 block text-xl">Assign capabilities</strong>
                </a>
            @endcan
            @can('admin.permissions.manage')
                <a href="{{ route('admin.permissions.index') }}" class="rounded-2xl border border-white/10 bg-white/[0.03] p-5 transition hover:bg-white/[0.06]">
                    <span class="text-sm text-zinc-400">Permissions</span>
                    <strong class="mt-3 block text-xl">Control access</strong>
                </a>
            @endcan
            @can('admin.plugins.manage')
                <a href="{{ route('admin.plugins.index') }}" class="rounded-2xl border border-white/10 bg-white/[0.03] p-5 transition hover:bg-white/[0.06]">
                    <span class="text-sm text-zinc-400">Plugins</span>
                    <strong class="mt-3 block text-xl">Manage extensions</strong>
                </a>
            @endcan
        </div>
    </main>
</x-layouts.app>
