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
                <p class="text-sm uppercase tracking-[0.18em] text-emerald-300">Personal operating system</p>
                <h1 class="mt-5 max-w-3xl text-5xl font-semibold tracking-tight md:text-6xl">Create your Life Wheel, track your history, and get AI coaching from your own data.</h1>
                <p class="mt-6 max-w-2xl text-lg leading-8 text-zinc-300">Score the core areas of your life from 1 to 10, save each check-in, compare progress over time, and use AI to understand where you have come from, where you are, and where to focus next.</p>
                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ route('member.dashboard') }}" class="rounded-xl bg-white px-5 py-3 text-center text-sm font-semibold text-zinc-950">Open my dashboard</a>
                    <a href="{{ route('login') }}" class="rounded-xl border border-white/10 px-5 py-3 text-center text-sm text-zinc-200">Sign in</a>
                </div>
            </div>
            <div class="rounded-xl border border-white/10 bg-white/5 p-6">
                <h2 class="text-xl font-semibold">How it works</h2>
                <ul class="mt-5 space-y-3 text-sm text-zinc-300">
                    <li>1. Rate each Life Wheel category from 1 to 10.</li>
                    <li>2. Save every wheel so your history is never overwritten.</li>
                    <li>3. Use AI Analysis and AI Coach for personalized feedback from past and present wheels.</li>
                </ul>
            </div>
        </section>
    </main>
</x-layouts.app>
