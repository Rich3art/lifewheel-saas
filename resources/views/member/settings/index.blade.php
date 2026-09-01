<x-layouts.app title="Settings">
    <main class="mx-auto w-full max-w-6xl px-6 py-10">
        <div class="flex flex-col gap-4 border-b border-white/10 pb-8 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm text-zinc-400">Member settings</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight">Your account controls</h1>
            </div>
            <a href="{{ route('member.dashboard') }}" class="rounded-xl border border-white/10 px-4 py-2 text-center text-sm text-zinc-200">Dashboard</a>
        </div>

        @if (session('status') === 'privacy-request-created')
            <div class="mt-6 rounded-xl border border-emerald-400/30 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-100">Privacy request received.</div>
        @endif

        <div class="mt-8 grid gap-6 lg:grid-cols-[220px_1fr]">
            <nav class="space-y-2">
                @foreach ($sections as $section)
                    <a href="#{{ $section->key }}" class="block rounded-xl border border-white/10 bg-white/[0.03] px-4 py-3 text-sm text-zinc-200">{{ $section->label }}</a>
                @endforeach
            </nav>

            <div class="space-y-6">
                @if ($sections->contains('key', 'profile'))
                    <section id="profile" class="rounded-2xl border border-white/10 bg-white/[0.03] p-6">
                        <h2 class="text-lg font-semibold">Profile</h2>
                        <p class="mt-2 text-sm text-zinc-400">Update your public account identity and timezone.</p>
                        <a href="{{ route('profile.edit') }}" class="mt-5 inline-flex rounded-xl bg-white px-4 py-3 text-sm font-semibold text-zinc-950">Edit profile</a>
                    </section>
                @endif

                @if ($sections->contains('key', 'security'))
                    <section id="security" class="rounded-2xl border border-white/10 bg-white/[0.03] p-6">
                        <h2 class="text-lg font-semibold">Security</h2>
                        <p class="mt-2 text-sm text-zinc-400">Manage your password and two-factor authenticator protection.</p>
                        <div class="mt-5 flex flex-wrap gap-3">
                            <a href="{{ route('profile.edit') }}" class="rounded-xl border border-white/10 px-4 py-3 text-sm text-zinc-200">Change password</a>
                            <a href="{{ route('two-factor.show') }}" class="rounded-xl border border-white/10 px-4 py-3 text-sm text-zinc-200">Manage 2FA</a>
                        </div>
                    </section>
                @endif

                @if ($sections->contains('key', 'privacy'))
                    <section id="privacy" class="rounded-2xl border border-white/10 bg-white/[0.03] p-6">
                        <h2 class="text-lg font-semibold">Privacy</h2>
                        <p class="mt-2 text-sm text-zinc-400">Request a data export, correction, consent review, or erasure workflow.</p>
                        <form method="POST" action="{{ route('member.privacy-requests.store') }}" class="mt-5 space-y-4">
                            @csrf
                            <select name="type" class="w-full rounded-xl border border-white/10 bg-zinc-900 px-4 py-3 text-sm">
                                <option value="data_export">Data export</option>
                                <option value="correction">Correction request</option>
                                <option value="consent_review">Consent review</option>
                                <option value="erasure">Erasure request</option>
                            </select>
                            <textarea name="details" rows="4" placeholder="Add helpful context for this request." class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm"></textarea>
                            <button class="rounded-xl bg-white px-4 py-3 text-sm font-semibold text-zinc-950">Submit request</button>
                        </form>
                        <div class="mt-6 space-y-2">
                            @forelse ($privacyRequests as $privacyRequest)
                                <div class="rounded-xl border border-white/10 px-4 py-3 text-sm text-zinc-300">
                                    {{ ucfirst(str_replace('_', ' ', $privacyRequest->type)) }} - {{ ucfirst(str_replace('_', ' ', $privacyRequest->status)) }} - {{ $privacyRequest->created_at?->format('Y-m-d') }}
                                </div>
                            @empty
                                <p class="text-sm text-zinc-500">No privacy requests yet.</p>
                            @endforelse
                        </div>
                    </section>
                @endif

                @if ($sections->contains('key', 'billing'))
                    <section id="billing" class="rounded-2xl border border-white/10 bg-white/[0.03] p-6">
                        <h2 class="text-lg font-semibold">Billing</h2>
                        <a href="{{ route('member.billing.index') }}" class="mt-5 inline-flex rounded-xl bg-white px-4 py-3 text-sm font-semibold text-zinc-950">Open billing history</a>
                        @forelse ($user->packages as $package)
                            <div class="mt-4 rounded-xl border border-white/10 px-4 py-3 text-sm text-zinc-300">
                                {{ $package->name }} - {{ ucfirst($package->pivot->status) }}
                            </div>
                        @empty
                            <p class="mt-2 text-sm text-zinc-400">No package is assigned yet.</p>
                        @endforelse
                    </section>
                @endif
            </div>
        </div>
    </main>
</x-layouts.app>
