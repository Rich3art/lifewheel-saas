<x-layouts.app title="Sign in to LifeWheel">
    <main class="mx-auto flex min-h-screen w-full max-w-md flex-col justify-center px-6 py-12">
        <a href="{{ route('home') }}" class="mb-8 text-sm text-zinc-400">LifeWheel SaaS</a>
        <h1 class="text-3xl font-semibold tracking-tight">Welcome back</h1>
        <p class="mt-3 text-sm leading-6 text-zinc-400">Sign in to continue to your operating system.</p>

        <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
            @csrf
            <label class="block">
                <span class="text-sm text-zinc-300">Email</span>
                <input name="email" type="email" value="{{ old('email') }}" required autocomplete="email" autofocus class="mt-2 w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm outline-none ring-emerald-300/40 focus:ring">
                @error('email')<span class="mt-2 block text-sm text-red-300">{{ $message }}</span>@enderror
            </label>
            <label class="block">
                <span class="text-sm text-zinc-300">Password</span>
                <input name="password" type="password" required autocomplete="current-password" class="mt-2 w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm outline-none ring-emerald-300/40 focus:ring">
                @error('password')<span class="mt-2 block text-sm text-red-300">{{ $message }}</span>@enderror
            </label>
            <label class="flex items-center gap-3 text-sm text-zinc-300">
                <input name="remember" value="1" type="checkbox" class="rounded border-white/20 bg-white/5">
                Remember this device
            </label>
            <button class="w-full rounded-xl bg-white px-4 py-3 text-sm font-semibold text-zinc-950 transition hover:bg-zinc-200">Sign in</button>
        </form>
        <div class="mt-6 flex justify-between text-sm text-zinc-400">
            <a class="underline" href="{{ route('password.request') }}">Forgot password?</a>
            <a class="underline" href="{{ route('register') }}">Create account</a>
        </div>
    </main>
</x-layouts.app>
