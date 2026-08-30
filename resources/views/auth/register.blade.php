<x-layouts.app title="Create your LifeWheel account">
    <main class="mx-auto flex min-h-screen w-full max-w-md flex-col justify-center px-6 py-12">
        <a href="{{ route('home') }}" class="mb-8 text-sm text-zinc-400">LifeWheel SaaS</a>
        <h1 class="text-3xl font-semibold tracking-tight">Create your account</h1>
        <p class="mt-3 text-sm leading-6 text-zinc-400">Access is package-controlled. After sign-up, your account must have an active entitlement before product features open.</p>

        <form method="POST" action="{{ route('register') }}" class="mt-8 space-y-5">
            @csrf
            <label class="block">
                <span class="text-sm text-zinc-300">Name</span>
                <input name="name" value="{{ old('name') }}" required autocomplete="name" class="mt-2 w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm outline-none ring-emerald-300/40 focus:ring">
                @error('name')<span class="mt-2 block text-sm text-red-300">{{ $message }}</span>@enderror
            </label>
            <label class="block">
                <span class="text-sm text-zinc-300">Email</span>
                <input name="email" type="email" value="{{ old('email') }}" required autocomplete="email" class="mt-2 w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm outline-none ring-emerald-300/40 focus:ring">
                @error('email')<span class="mt-2 block text-sm text-red-300">{{ $message }}</span>@enderror
            </label>
            <label class="block">
                <span class="text-sm text-zinc-300">Timezone</span>
                <select name="timezone" class="mt-2 w-full rounded-xl border border-white/10 bg-zinc-900 px-4 py-3 text-sm outline-none ring-emerald-300/40 focus:ring">
                    @foreach (timezone_identifiers_list() as $timezone)
                        <option value="{{ $timezone }}" @selected(old('timezone', 'UTC') === $timezone)>{{ $timezone }}</option>
                    @endforeach
                </select>
                @error('timezone')<span class="mt-2 block text-sm text-red-300">{{ $message }}</span>@enderror
            </label>
            <label class="block">
                <span class="text-sm text-zinc-300">Password</span>
                <input name="password" type="password" required autocomplete="new-password" class="mt-2 w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm outline-none ring-emerald-300/40 focus:ring">
                @error('password')<span class="mt-2 block text-sm text-red-300">{{ $message }}</span>@enderror
            </label>
            <label class="block">
                <span class="text-sm text-zinc-300">Confirm password</span>
                <input name="password_confirmation" type="password" required autocomplete="new-password" class="mt-2 w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm outline-none ring-emerald-300/40 focus:ring">
            </label>
            <button class="w-full rounded-xl bg-white px-4 py-3 text-sm font-semibold text-zinc-950 transition hover:bg-zinc-200">Create account</button>
        </form>
        <p class="mt-6 text-sm text-zinc-400">Already registered? <a class="text-white underline" href="{{ route('login') }}">Sign in</a></p>
    </main>
</x-layouts.app>
