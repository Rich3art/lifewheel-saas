<x-layouts.app title="Reset password">
    <main class="mx-auto flex min-h-screen w-full max-w-md flex-col justify-center px-6 py-12">
        <h1 class="text-3xl font-semibold tracking-tight">Reset password</h1>
        <p class="mt-3 text-sm leading-6 text-zinc-400">Enter your email and we will send a secure reset link.</p>
        <form method="POST" action="{{ route('password.email') }}" class="mt-8 space-y-5">
            @csrf
            <label class="block">
                <span class="text-sm text-zinc-300">Email</span>
                <input name="email" type="email" value="{{ old('email') }}" required class="mt-2 w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm outline-none ring-emerald-300/40 focus:ring">
                @error('email')<span class="mt-2 block text-sm text-red-300">{{ $message }}</span>@enderror
            </label>
            <button class="w-full rounded-xl bg-white px-4 py-3 text-sm font-semibold text-zinc-950">Send reset link</button>
        </form>
    </main>
</x-layouts.app>
