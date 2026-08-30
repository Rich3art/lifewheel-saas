<x-layouts.app title="Verify email">
    <main class="mx-auto flex min-h-screen w-full max-w-lg flex-col justify-center px-6 py-12">
        <h1 class="text-3xl font-semibold tracking-tight">Verify your email</h1>
        <p class="mt-3 text-sm leading-6 text-zinc-400">We sent a verification link to your email address. Verified email is required before accessing the member area.</p>
        <form method="POST" action="{{ route('verification.send') }}" class="mt-8">
            @csrf
            <button class="rounded-xl bg-white px-4 py-3 text-sm font-semibold text-zinc-950">Send another link</button>
        </form>
        <form method="POST" action="{{ route('logout') }}" class="mt-4">
            @csrf
            <button class="text-sm text-zinc-400 underline">Sign out</button>
        </form>
    </main>
</x-layouts.app>
