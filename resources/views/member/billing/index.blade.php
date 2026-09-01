<x-layouts.app title="Billing">
    <main class="mx-auto max-w-6xl px-6 py-10">
        <div class="flex flex-col gap-4 border-b border-white/10 pb-8 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm text-zinc-400">Member billing</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight">Your subscription</h1>
            </div>
            <a href="{{ route('member.settings') }}" class="rounded-xl border border-white/10 px-4 py-2 text-center text-sm text-zinc-200">Settings</a>
        </div>

        <section class="mt-8 rounded-2xl border border-white/10 bg-white/[0.03] p-6">
            <h2 class="text-lg font-semibold">Subscriptions</h2>
            <div class="mt-4 space-y-3">
                @forelse ($subscriptions as $subscription)
                    <div class="rounded-xl border border-white/10 px-4 py-3 text-sm text-zinc-300">
                        <p class="font-semibold text-zinc-100">{{ $subscription->package->name }}</p>
                        <p class="mt-1 text-zinc-500">{{ ucfirst($subscription->status) }} / {{ $subscription->provider_key }} / {{ $subscription->currency }} {{ number_format($subscription->amount_cents / 100, 2) }}</p>
                        @if ($subscription->current_period_ends_at)
                            <p class="mt-1 text-xs text-zinc-600">Renews or expires {{ $subscription->current_period_ends_at->format('Y-m-d') }}</p>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-zinc-400">No subscription history yet.</p>
                @endforelse
            </div>
        </section>

        <section class="mt-6 rounded-2xl border border-white/10 bg-white/[0.03] p-6">
            <h2 class="text-lg font-semibold">Available packages</h2>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                @forelse ($packages as $package)
                    <div class="rounded-xl border border-white/10 p-4">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="font-semibold text-zinc-100">{{ $package->name }}</p>
                                <p class="mt-1 text-sm text-zinc-500">{{ $package->short_description }}</p>
                            </div>
                            <span class="text-sm text-zinc-300">{{ $package->currency }} {{ number_format($package->price_cents / 100, 2) }}</span>
                        </div>
                        <div class="mt-4 flex flex-wrap gap-2">
                            @forelse ($package->providerMappings as $mapping)
                                <form method="POST" action="{{ route('member.billing.checkout') }}">
                                    @csrf
                                    <input type="hidden" name="mapping_id" value="{{ $mapping->id }}">
                                    <button class="rounded-lg bg-white px-3 py-2 text-sm font-semibold text-zinc-950">
                                        Checkout with {{ $mapping->provider->name }}
                                    </button>
                                </form>
                            @empty
                                <span class="text-sm text-zinc-500">No checkout provider is enabled for this package.</span>
                            @endforelse
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-zinc-400">No public packages are available.</p>
                @endforelse
            </div>
        </section>

        <section class="mt-6 rounded-2xl border border-white/10 bg-white/[0.03] p-6">
            <h2 class="text-lg font-semibold">Invoices</h2>
            <div class="mt-4 space-y-3">
                @forelse ($invoices as $invoice)
                    <div class="rounded-xl border border-white/10 px-4 py-3 text-sm text-zinc-300">
                        {{ ucfirst($invoice->status) }} / {{ $invoice->provider_key }} / {{ $invoice->currency }} {{ number_format($invoice->amount_cents / 100, 2) }}
                    </div>
                @empty
                    <p class="text-sm text-zinc-400">No invoices yet.</p>
                @endforelse
            </div>
        </section>
    </main>
</x-layouts.app>
