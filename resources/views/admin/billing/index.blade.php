<x-layouts.app title="Billing">
    <main class="mx-auto max-w-7xl px-6 py-10">
        <div class="border-b border-white/10 pb-8">
            <p class="text-sm text-zinc-400">Super Admin</p>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight">Billing Core</h1>
        </div>

        <div class="mt-8 grid gap-6 xl:grid-cols-2">
            <section class="rounded-2xl border border-white/10 bg-white/[0.03] p-6">
                <h2 class="text-lg font-semibold">Payment providers</h2>
                <div class="mt-5 space-y-4">
                    @foreach ($providers as $provider)
                        <form method="POST" action="{{ route('admin.billing.providers.update', $provider) }}" class="rounded-xl border border-white/10 p-4">
                            @csrf
                            @method('PUT')
                            <div class="grid gap-3 md:grid-cols-[1fr_auto_auto]">
                                <input name="name" value="{{ $provider->name }}" class="rounded-xl border border-white/10 bg-zinc-950/60 px-4 py-3 text-sm">
                                <label class="flex items-center gap-2 text-sm text-zinc-300"><input type="checkbox" name="enabled" value="1" @checked($provider->enabled)> Enabled</label>
                                <label class="flex items-center gap-2 text-sm text-zinc-300"><input type="checkbox" name="sandbox" value="1" @checked($provider->sandbox)> Sandbox</label>
                            </div>
                            <div class="mt-3 flex items-center justify-between text-xs text-zinc-500">
                                <span>{{ $provider->key }} / {{ $provider->mappings_count }} mappings</span>
                                <button class="rounded-lg border border-white/10 px-3 py-2 text-zinc-300">Save</button>
                            </div>
                        </form>
                    @endforeach
                </div>
            </section>

            <section class="rounded-2xl border border-white/10 bg-white/[0.03] p-6">
                <h2 class="text-lg font-semibold">Create provider mapping</h2>
                <form method="POST" action="{{ route('admin.billing.mappings.store') }}" class="mt-5 space-y-4">
                    @csrf
                    <div class="grid gap-3 md:grid-cols-2">
                        <select name="package_id" required class="rounded-xl border border-white/10 bg-zinc-900 px-4 py-3 text-sm">
                            @foreach ($packages as $package)
                                <option value="{{ $package->id }}">{{ $package->name }}</option>
                            @endforeach
                        </select>
                        <select name="payment_provider_id" required class="rounded-xl border border-white/10 bg-zinc-900 px-4 py-3 text-sm">
                            @foreach ($providers as $provider)
                                <option value="{{ $provider->id }}">{{ $provider->name }}</option>
                            @endforeach
                        </select>
                        <input name="external_product_id" placeholder="External product ID" class="rounded-xl border border-white/10 bg-zinc-950/60 px-4 py-3 text-sm">
                        <input name="external_price_id" placeholder="External price ID" class="rounded-xl border border-white/10 bg-zinc-950/60 px-4 py-3 text-sm">
                        <input name="amount_cents" type="number" min="0" placeholder="Amount cents" class="rounded-xl border border-white/10 bg-zinc-950/60 px-4 py-3 text-sm">
                        <input name="currency" maxlength="3" placeholder="USD" class="rounded-xl border border-white/10 bg-zinc-950/60 px-4 py-3 text-sm">
                    </div>
                    <label class="flex items-center gap-2 text-sm text-zinc-300"><input type="checkbox" name="active" value="1" checked> Active</label>
                    <button class="rounded-xl bg-white px-4 py-3 text-sm font-semibold text-zinc-950">Create mapping</button>
                </form>
            </section>
        </div>

        <section class="mt-6 rounded-2xl border border-white/10 bg-white/[0.03] p-6">
            <h2 class="text-lg font-semibold">Manual subscription activation</h2>
            <form method="POST" action="{{ route('admin.billing.subscriptions.manual') }}" class="mt-5 grid gap-3 md:grid-cols-3">
                @csrf
                <select name="user_id" required class="rounded-xl border border-white/10 bg-zinc-900 px-4 py-3 text-sm">
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}">{{ $user->email }}</option>
                    @endforeach
                </select>
                <select name="package_id" required class="rounded-xl border border-white/10 bg-zinc-900 px-4 py-3 text-sm">
                    @foreach ($packages as $package)
                        <option value="{{ $package->id }}">{{ $package->name }}</option>
                    @endforeach
                </select>
                <select name="billing_interval" class="rounded-xl border border-white/10 bg-zinc-900 px-4 py-3 text-sm">
                    @foreach (['monthly', 'quarterly', 'yearly', 'lifetime'] as $interval)
                        <option value="{{ $interval }}">{{ ucfirst($interval) }}</option>
                    @endforeach
                </select>
                <input name="amount_cents" type="number" min="0" value="0" class="rounded-xl border border-white/10 bg-zinc-950/60 px-4 py-3 text-sm">
                <input name="currency" maxlength="3" value="USD" class="rounded-xl border border-white/10 bg-zinc-950/60 px-4 py-3 text-sm">
                <input name="current_period_ends_at" type="date" class="rounded-xl border border-white/10 bg-zinc-950/60 px-4 py-3 text-sm">
                <button class="rounded-xl bg-white px-4 py-3 text-sm font-semibold text-zinc-950 md:col-span-3">Activate subscription</button>
            </form>
        </section>

        <section class="mt-6 rounded-2xl border border-white/10 bg-white/[0.03] p-6">
            <h2 class="text-lg font-semibold">Recent subscriptions</h2>
            <div class="mt-4 space-y-3">
                @forelse ($subscriptions as $subscription)
                    <div class="rounded-xl border border-white/10 p-4">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <div class="text-sm text-zinc-300">
                                <p class="font-semibold text-zinc-100">{{ $subscription->user->email }} / {{ $subscription->package->name }}</p>
                                <p class="mt-1 text-zinc-500">{{ $subscription->provider_key }} / {{ $subscription->status }} / {{ $subscription->currency }} {{ number_format($subscription->amount_cents / 100, 2) }}</p>
                            </div>
                            <form method="POST" action="{{ route('admin.billing.subscriptions.cancel', $subscription) }}">
                                @csrf
                                @method('PUT')
                                <label class="mr-3 text-sm text-zinc-400"><input type="checkbox" name="immediate" value="1"> Immediate</label>
                                <button class="rounded-xl border border-white/10 px-4 py-2 text-sm text-zinc-200">Cancel</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-zinc-400">No subscriptions yet.</p>
                @endforelse
            </div>
        </section>
    </main>
</x-layouts.app>
