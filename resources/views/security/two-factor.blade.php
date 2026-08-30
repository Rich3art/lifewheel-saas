<x-layouts.app title="Two-factor authentication">
    <main class="mx-auto w-full max-w-3xl px-6 py-10">
        <div class="border-b border-white/10 pb-8">
            <a href="{{ route('profile.edit') }}" class="text-sm text-zinc-400">Account settings</a>
            <h1 class="mt-3 text-3xl font-semibold tracking-tight">Two-factor authentication</h1>
            <p class="mt-3 text-sm leading-6 text-zinc-400">Use an authenticator app to add a second step at sign-in.</p>
        </div>

        @if (! $user->hasEnabledTwoFactorAuthentication())
            <section class="mt-8 rounded-2xl border border-white/10 bg-white/[0.03] p-6">
                <h2 class="text-lg font-semibold">Set up authenticator</h2>
                <p class="mt-3 text-sm leading-6 text-zinc-400">Add this setup key to your authenticator app, then enter the generated six-digit code.</p>
                <div class="mt-5 overflow-auto rounded-xl border border-white/10 bg-black/30 p-4 text-sm text-zinc-200">{{ $secret }}</div>
                <div class="mt-3 overflow-auto rounded-xl border border-white/10 bg-black/30 p-4 text-xs text-zinc-400">{{ $otpauthUrl }}</div>
                <form method="POST" action="{{ route('two-factor.confirm') }}" class="mt-6 space-y-4">
                    @csrf
                    <label class="block">
                        <span class="text-sm text-zinc-300">Authenticator code</span>
                        <input name="code" inputmode="numeric" required class="mt-2 w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm outline-none ring-emerald-300/40 focus:ring">
                        @error('code')<span class="mt-2 block text-sm text-red-300">{{ $message }}</span>@enderror
                    </label>
                    <button class="rounded-xl bg-white px-4 py-3 text-sm font-semibold text-zinc-950">Enable 2FA</button>
                </form>
            </section>
        @else
            <section class="mt-8 rounded-2xl border border-white/10 bg-white/[0.03] p-6">
                <h2 class="text-lg font-semibold">2FA is enabled</h2>
                <p class="mt-3 text-sm leading-6 text-zinc-400">Keep these recovery codes somewhere secure. Each code can be used once.</p>
                <div class="mt-5 grid gap-2 sm:grid-cols-2">
                    @foreach ($user->recoveryCodes() as $code)
                        <code class="rounded-lg border border-white/10 bg-black/30 px-3 py-2 text-sm text-zinc-200">{{ $code }}</code>
                    @endforeach
                </div>
                <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                    <form method="POST" action="{{ route('two-factor.recovery-codes') }}">
                        @csrf
                        <button class="rounded-xl border border-white/10 px-4 py-3 text-sm text-zinc-200">Regenerate recovery codes</button>
                    </form>
                    <form method="POST" action="{{ route('two-factor.disable') }}" class="flex flex-col gap-3 sm:flex-row">
                        @csrf
                        @method('DELETE')
                        <input name="password" type="password" required placeholder="Current password" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm outline-none ring-red-300/40 focus:ring">
                        <button class="rounded-xl bg-red-300 px-4 py-3 text-sm font-semibold text-red-950">Disable 2FA</button>
                    </form>
                </div>
            </section>
        @endif
    </main>
</x-layouts.app>
