<x-layouts.app title="Account settings">
    <main class="mx-auto w-full max-w-5xl px-6 py-10">
        <div class="flex flex-col gap-4 border-b border-white/10 pb-8 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm text-zinc-400">LifeWheel SaaS</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight">Account settings</h1>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('member.dashboard') }}" class="rounded-xl border border-white/10 px-4 py-2 text-sm text-zinc-200">Dashboard</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="rounded-xl bg-white px-4 py-2 text-sm font-semibold text-zinc-950">Sign out</button>
                </form>
            </div>
        </div>

        <div class="mt-8 grid gap-6 lg:grid-cols-2">
            <section class="rounded-2xl border border-white/10 bg-white/[0.03] p-6">
                <h2 class="text-lg font-semibold">Profile</h2>
                <form method="POST" action="{{ route('profile.update') }}" class="mt-6 space-y-5">
                    @csrf
                    @method('PATCH')
                    <label class="block">
                        <span class="text-sm text-zinc-300">Name</span>
                        <input name="name" value="{{ old('name', $user->name) }}" required class="mt-2 w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm outline-none ring-emerald-300/40 focus:ring">
                        @error('name')<span class="mt-2 block text-sm text-red-300">{{ $message }}</span>@enderror
                    </label>
                    <label class="block">
                        <span class="text-sm text-zinc-300">Username</span>
                        <input name="username" value="{{ old('username', $user->username) }}" class="mt-2 w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm outline-none ring-emerald-300/40 focus:ring">
                        @error('username')<span class="mt-2 block text-sm text-red-300">{{ $message }}</span>@enderror
                    </label>
                    <label class="block">
                        <span class="text-sm text-zinc-300">Timezone</span>
                        <select name="timezone" class="mt-2 w-full rounded-xl border border-white/10 bg-zinc-900 px-4 py-3 text-sm outline-none ring-emerald-300/40 focus:ring">
                            @foreach (timezone_identifiers_list() as $timezone)
                                <option value="{{ $timezone }}" @selected(old('timezone', $user->timezone) === $timezone)>{{ $timezone }}</option>
                            @endforeach
                        </select>
                        @error('timezone')<span class="mt-2 block text-sm text-red-300">{{ $message }}</span>@enderror
                    </label>
                    <button class="rounded-xl bg-white px-4 py-3 text-sm font-semibold text-zinc-950">Save profile</button>
                </form>
            </section>

            <section class="rounded-2xl border border-white/10 bg-white/[0.03] p-6">
                <h2 class="text-lg font-semibold">Password</h2>
                <form method="POST" action="{{ route('profile.password') }}" class="mt-6 space-y-5">
                    @csrf
                    @method('PUT')
                    <label class="block">
                        <span class="text-sm text-zinc-300">Current password</span>
                        <input name="current_password" type="password" required class="mt-2 w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm outline-none ring-emerald-300/40 focus:ring">
                        @error('current_password')<span class="mt-2 block text-sm text-red-300">{{ $message }}</span>@enderror
                    </label>
                    <label class="block">
                        <span class="text-sm text-zinc-300">New password</span>
                        <input name="password" type="password" required class="mt-2 w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm outline-none ring-emerald-300/40 focus:ring">
                        @error('password')<span class="mt-2 block text-sm text-red-300">{{ $message }}</span>@enderror
                    </label>
                    <label class="block">
                        <span class="text-sm text-zinc-300">Confirm password</span>
                        <input name="password_confirmation" type="password" required class="mt-2 w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm outline-none ring-emerald-300/40 focus:ring">
                    </label>
                    <button class="rounded-xl bg-white px-4 py-3 text-sm font-semibold text-zinc-950">Update password</button>
                </form>
            </section>
        </div>

        <section class="mt-6 rounded-2xl border border-white/10 bg-white/[0.03] p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold">Two-factor authentication</h2>
                    <p class="mt-1 text-sm text-zinc-400">Protect your account with a standards-based authenticator app.</p>
                </div>
                <a href="{{ route('two-factor.show') }}" class="rounded-xl border border-white/10 px-4 py-3 text-center text-sm text-zinc-200">Manage 2FA</a>
            </div>
        </section>
    </main>
</x-layouts.app>
