<x-layouts.app title="Choose a new password">
    <main class="mx-auto flex min-h-screen w-full max-w-md flex-col justify-center px-6 py-12">
        <h1 class="text-3xl font-semibold tracking-tight">Choose a new password</h1>
        <form method="POST" action="{{ route('password.store') }}" class="mt-8 space-y-5">
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}">
            <label class="block">
                <span class="text-sm text-zinc-300">Email</span>
                <input name="email" type="email" value="{{ old('email', $request->email) }}" required class="mt-2 w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm outline-none ring-emerald-300/40 focus:ring">
                @error('email')<span class="mt-2 block text-sm text-red-300">{{ $message }}</span>@enderror
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
            <button class="w-full rounded-xl bg-white px-4 py-3 text-sm font-semibold text-zinc-950">Update password</button>
        </form>
    </main>
</x-layouts.app>
