<x-layouts.app title="Roles">
    <main class="mx-auto max-w-6xl px-6 py-10">
        <a href="{{ route('admin.dashboard') }}" class="text-sm text-zinc-400">Admin</a>
        <h1 class="mt-2 text-3xl font-semibold">Roles</h1>

        <section class="mt-8 rounded-2xl border border-white/10 bg-white/[0.03] p-6">
            <h2 class="text-lg font-semibold">Create role</h2>
            <form method="POST" action="{{ route('admin.roles.store') }}" class="mt-5 grid gap-4 lg:grid-cols-[1fr_1fr_1fr_auto]">
                @csrf
                <input name="name" placeholder="Role name" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm outline-none ring-emerald-300/40 focus:ring">
                <input name="slug" placeholder="custom-role" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm outline-none ring-emerald-300/40 focus:ring">
                <input name="description" placeholder="Description" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm outline-none ring-emerald-300/40 focus:ring">
                <button class="rounded-xl bg-white px-4 py-3 text-sm font-semibold text-zinc-950">Create</button>
            </form>
        </section>

        <div class="mt-6 grid gap-4">
            @foreach ($roles as $role)
                <form method="POST" action="{{ route('admin.roles.update', $role) }}" class="rounded-2xl border border-white/10 bg-white/[0.03] p-6">
                    @csrf
                    @method('PUT')
                    <div class="grid gap-4 lg:grid-cols-2">
                        <label class="block">
                            <span class="text-sm text-zinc-300">Name</span>
                            <input name="name" value="{{ $role->name }}" class="mt-2 w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm outline-none ring-emerald-300/40 focus:ring">
                        </label>
                        <label class="block">
                            <span class="text-sm text-zinc-300">Slug</span>
                            <input name="slug" value="{{ $role->slug }}" @disabled($role->is_system) class="mt-2 w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm outline-none ring-emerald-300/40 focus:ring disabled:opacity-60">
                        </label>
                        <label class="block">
                            <span class="text-sm text-zinc-300">Description</span>
                            <input name="description" value="{{ $role->description }}" class="mt-2 w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm outline-none ring-emerald-300/40 focus:ring">
                        </label>
                    </div>
                    <div class="mt-5 grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ($permissions as $permission)
                            <label class="flex items-center gap-2 text-sm text-zinc-300">
                                <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" @checked($role->permissions->contains($permission))>
                                {{ $permission->name }}
                            </label>
                        @endforeach
                    </div>
                    <div class="mt-5 flex items-center justify-between">
                        <span class="text-sm text-zinc-400">
                            {{ $role->users_count }} users
                            @if ($role->is_protected)
                                - protected
                            @endif
                        </span>
                        <button class="rounded-xl bg-white px-4 py-3 text-sm font-semibold text-zinc-950">Save role</button>
                    </div>
                </form>
            @endforeach
        </div>
    </main>
</x-layouts.app>
