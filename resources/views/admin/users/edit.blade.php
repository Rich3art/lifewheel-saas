<x-layouts.app title="Manage user">
    <main class="mx-auto max-w-4xl px-6 py-10">
        <a href="{{ route('admin.users.index') }}" class="text-sm text-zinc-400">Users</a>
        <div class="mt-3 border-b border-white/10 pb-8">
            <h1 class="text-3xl font-semibold">{{ $user->name }}</h1>
            <p class="mt-2 text-sm text-zinc-400">{{ $user->email }}</p>
        </div>

        <section class="mt-8 rounded-2xl border border-white/10 bg-white/[0.03] p-6">
            <h2 class="text-lg font-semibold">Roles</h2>
            <form method="POST" action="{{ route('admin.users.roles.update', $user) }}" class="mt-5 space-y-4">
                @csrf
                @method('PUT')
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach ($roles as $role)
                        <label class="flex items-start gap-3 rounded-xl border border-white/10 bg-black/20 p-4 text-sm">
                            <input type="checkbox" name="roles[]" value="{{ $role->id }}" @checked($user->roles->contains($role)) class="mt-1">
                            <span>
                                <span class="block font-medium text-white">{{ $role->name }}</span>
                                <span class="text-zinc-400">{{ $role->description }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
                <button class="rounded-xl bg-white px-4 py-3 text-sm font-semibold text-zinc-950">Save roles</button>
            </form>
        </section>

        <section class="mt-6 rounded-2xl border border-white/10 bg-white/[0.03] p-6">
            <h2 class="text-lg font-semibold">Package</h2>
            <form method="POST" action="{{ route('admin.users.packages.update', $user) }}" class="mt-5 grid gap-4 sm:grid-cols-[1fr_1fr_auto]">
                @csrf
                @method('PUT')
                <select name="package_id" class="rounded-xl border border-white/10 bg-zinc-900 px-4 py-3 text-sm">
                    <option value="">No package</option>
                    @foreach ($packages as $package)
                        <option value="{{ $package->id }}" @selected($user->packages->contains($package))>{{ $package->name }}</option>
                    @endforeach
                </select>
                <input name="ends_at" type="date" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                <button class="rounded-xl bg-white px-4 py-3 text-sm font-semibold text-zinc-950">Assign</button>
            </form>
        </section>

        <section class="mt-6 rounded-2xl border border-white/10 bg-white/[0.03] p-6">
            <h2 class="text-lg font-semibold">Feature overrides</h2>
            <form method="POST" action="{{ route('admin.users.feature-overrides.update', $user) }}" class="mt-5 space-y-4">
                @csrf
                @method('PUT')
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach ($features as $feature)
                        @php $override = $user->featureOverrides->firstWhere('feature_id', $feature->id); @endphp
                        <label class="rounded-xl border border-white/10 bg-black/20 p-4 text-sm">
                            <span class="block font-medium text-white">{{ $feature->name }}</span>
                            <span class="block text-zinc-400">{{ $feature->slug }}</span>
                            <select name="overrides[{{ $feature->id }}]" class="mt-3 w-full rounded-lg border border-white/10 bg-zinc-900 px-3 py-2 text-xs">
                                <option value="">Use package</option>
                                <option value="grant" @selected($override?->enabled === true)>Grant</option>
                                <option value="deny" @selected($override?->enabled === false)>Deny</option>
                            </select>
                        </label>
                    @endforeach
                </div>
                <input name="reason" placeholder="Reason for override" class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                <button class="rounded-xl bg-white px-4 py-3 text-sm font-semibold text-zinc-950">Save overrides</button>
            </form>
        </section>

        <section class="mt-6 rounded-2xl border border-white/10 bg-white/[0.03] p-6">
            <h2 class="text-lg font-semibold">Direct permissions</h2>
            <form method="POST" action="{{ route('admin.users.permissions.update', $user) }}" class="mt-5 space-y-4">
                @csrf
                @method('PUT')
                <div class="grid gap-2 sm:grid-cols-2">
                    @foreach ($permissions as $permission)
                        <label class="flex items-center gap-2 text-sm text-zinc-300">
                            <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" @checked($user->directPermissions->contains($permission))>
                            {{ $permission->name }}
                        </label>
                    @endforeach
                </div>
                <button class="rounded-xl bg-white px-4 py-3 text-sm font-semibold text-zinc-950">Save permissions</button>
            </form>
        </section>

        <section class="mt-6 rounded-2xl border border-white/10 bg-white/[0.03] p-6">
            <h2 class="text-lg font-semibold">Account status</h2>
            <p class="mt-2 text-sm text-zinc-400">Suspension blocks sign-in use and invalidates the current web session on the next request.</p>
            <div class="mt-5">
                @if ($user->isSuspended())
                    <form method="POST" action="{{ route('admin.users.unsuspend', $user) }}">
                        @csrf
                        @method('PUT')
                        <button class="rounded-xl bg-emerald-300 px-4 py-3 text-sm font-semibold text-emerald-950">Unsuspend user</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('admin.users.suspend', $user) }}">
                        @csrf
                        @method('PUT')
                        <button class="rounded-xl bg-red-300 px-4 py-3 text-sm font-semibold text-red-950">Suspend user</button>
                    </form>
                @endif
            </div>
        </section>
    </main>
</x-layouts.app>
