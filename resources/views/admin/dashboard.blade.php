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
            @can('admin.saas.manage')
                <a href="{{ route('admin.packages.index') }}" class="rounded-2xl border border-white/10 bg-white/[0.03] p-5 transition hover:bg-white/[0.06]">
                    <span class="text-sm text-zinc-400">SaaS</span>
                    <strong class="mt-3 block text-xl">Packages and features</strong>
                </a>
            @endcan
            @can('admin.billing.manage')
                <a href="{{ route('admin.billing.index') }}" class="rounded-2xl border border-white/10 bg-white/[0.03] p-5 transition hover:bg-white/[0.06]">
                    <span class="text-sm text-zinc-400">Billing</span>
                    <strong class="mt-3 block text-xl">Subscriptions</strong>
                </a>
            @endcan
            @can('admin.pages.manage')
                <a href="{{ route('admin.pages.index') }}" class="rounded-2xl border border-white/10 bg-white/[0.03] p-5 transition hover:bg-white/[0.06]">
                    <span class="text-sm text-zinc-400">CMS</span>
                    <strong class="mt-3 block text-xl">Public pages</strong>
                </a>
            @endcan
            @can('admin.blog.manage')
                <a href="{{ route('admin.blog.index') }}" class="rounded-2xl border border-white/10 bg-white/[0.03] p-5 transition hover:bg-white/[0.06]">
                    <span class="text-sm text-zinc-400">Publishing</span>
                    <strong class="mt-3 block text-xl">Blog</strong>
                </a>
            @endcan
            @can('admin.member_settings.manage')
                <a href="{{ route('admin.member-settings.index') }}" class="rounded-2xl border border-white/10 bg-white/[0.03] p-5 transition hover:bg-white/[0.06]">
                    <span class="text-sm text-zinc-400">Settings</span>
                    <strong class="mt-3 block text-xl">Member visibility</strong>
                </a>
            @endcan
            @can('admin.privacy.manage')
                <a href="{{ route('admin.privacy-requests.index') }}" class="rounded-2xl border border-white/10 bg-white/[0.03] p-5 transition hover:bg-white/[0.06]">
                    <span class="text-sm text-zinc-400">Privacy</span>
                    <strong class="mt-3 block text-xl">Requests queue</strong>
                </a>
            @endcan
            @can('admin.ai.manage')
                <a href="{{ route('admin.ai.index') }}" class="rounded-2xl border border-white/10 bg-white/[0.03] p-5 transition hover:bg-white/[0.06]">
                    <span class="text-sm text-zinc-400">AI</span>
                    <strong class="mt-3 block text-xl">Providers and routing</strong>
                </a>
            @endcan
        </div>
    </main>
</x-layouts.app>
