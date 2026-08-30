<x-layouts.app title="LifeWheel SaaS">
    <main class="mx-auto flex min-h-screen max-w-6xl flex-col px-6 py-6">
        <nav class="flex items-center justify-between rounded-xl border border-white/10 bg-white/5 px-4 py-3">
            <a href="{{ route('home') }}" class="font-semibold">LifeWheel SaaS</a>
            <div class="flex items-center gap-3 text-sm text-zinc-300">
                <a href="{{ route('member.dashboard') }}">Member</a>
                <a href="{{ route('admin.dashboard') }}">Admin</a>
                <a href="{{ route('health') }}">Health</a>
            </div>
        </nav>

        <section class="grid flex-1 items-center gap-8 py-16 lg:grid-cols-[1.1fr_.9fr]">
            <div>
                <p class="text-sm uppercase tracking-[0.18em] text-zinc-400">Commercial cPanel SaaS</p>
                <h1 class="mt-5 max-w-3xl text-5xl font-semibold tracking-tight md:text-6xl">A modular LifeWheel platform with a stable core and installable plugins.</h1>
                <p class="mt-6 max-w-2xl text-lg leading-8 text-zinc-300">Phase 1 establishes the Laravel foundation only: routing, shells, environment, assets, health checks, and cPanel-compatible structure.</p>
            </div>
            <div class="rounded-xl border border-white/10 bg-white/5 p-6">
                <h2 class="text-xl font-semibold">Foundation boundaries</h2>
                <ul class="mt-5 space-y-3 text-sm text-zinc-300">
                    <li>Core platform infrastructure belongs in Laravel core.</li>
                    <li>LifeWheel, AI Coach, Forum, Gamification, and payment providers will be plugins.</li>
                    <li>No permanent Node process is required in production.</li>
                </ul>
            </div>
        </section>
    </main>
</x-layouts.app>
