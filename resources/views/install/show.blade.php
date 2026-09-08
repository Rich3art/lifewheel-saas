<x-layouts.app title="Install LifeWheel SaaS">
    <main class="min-h-screen bg-[radial-gradient(circle_at_top_left,_rgba(34,197,94,0.16),_transparent_32rem),linear-gradient(135deg,_#09090b,_#18181b)] px-4 py-8 text-zinc-50 sm:px-6 lg:px-8">
        <div class="mx-auto grid max-w-6xl gap-6 lg:grid-cols-[0.9fr_1.1fr]">
            <section class="space-y-6">
                <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-6 shadow-2xl shadow-black/20 backdrop-blur">
                    <p class="text-sm font-medium uppercase tracking-[0.2em] text-emerald-300">cPanel installer</p>
                    <h1 class="mt-4 text-3xl font-semibold tracking-tight text-white sm:text-4xl">Install LifeWheel SaaS</h1>
                    <p class="mt-4 text-sm leading-6 text-zinc-300">
                        Configure the MySQL database, create the first Super Admin, run migrations, seed core roles, and lock the installer.
                    </p>
                </div>

                <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-6">
                    <div class="flex items-center justify-between gap-4">
                        <h2 class="text-lg font-semibold text-white">Server Checks</h2>
                        <span class="rounded-full border border-white/10 px-3 py-1 text-xs text-zinc-300">
                            {{ collect($checks)->where('ok', true)->count() }}/{{ count($checks) }} passing
                        </span>
                    </div>
                    <div class="mt-5 space-y-3">
                        @foreach ($checks as $check)
                            <div class="flex items-start justify-between gap-4 rounded-xl border border-white/10 bg-black/20 p-3">
                                <div>
                                    <p class="text-sm font-medium text-white">{{ $check['name'] }}</p>
                                    <p class="mt-1 break-all text-xs text-zinc-400">{{ $check['detail'] }}</p>
                                </div>
                                <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-medium {{ $check['ok'] ? 'bg-emerald-400/15 text-emerald-200' : 'bg-rose-400/15 text-rose-200' }}">
                                    {{ $check['ok'] ? 'Ready' : 'Fix' }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-white/10 bg-zinc-950/70 p-6 shadow-2xl shadow-black/25 backdrop-blur">
                @if ($errors->any())
                    <div class="mb-5 rounded-xl border border-rose-400/20 bg-rose-400/10 p-4 text-sm text-rose-100">
                        {{ $errors->first() }}
                    </div>
                @endif

                @if ($connectionResult)
                    <div class="mb-5 rounded-xl border {{ $connectionResult['ok'] ? 'border-emerald-400/20 bg-emerald-400/10 text-emerald-100' : 'border-rose-400/20 bg-rose-400/10 text-rose-100' }} p-4 text-sm">
                        {{ $connectionResult['detail'] }}
                    </div>
                @endif

                <form method="post" action="{{ route('install.store') }}" class="space-y-7">
                    @csrf
                    <div>
                        <h2 class="text-lg font-semibold text-white">Site</h2>
                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <label class="block">
                                <span class="text-sm text-zinc-300">Site name</span>
                                <input name="site_name" value="{{ old('site_name', 'LifeWheel SaaS') }}" required class="mt-2 w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white outline-none ring-emerald-400/40 focus:ring-2">
                            </label>
                            <label class="block">
                                <span class="text-sm text-zinc-300">Timezone</span>
                                <input name="timezone" value="{{ old('timezone', config('app.timezone', 'UTC')) }}" required class="mt-2 w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white outline-none ring-emerald-400/40 focus:ring-2">
                            </label>
                            <label class="block sm:col-span-2">
                                <span class="text-sm text-zinc-300">Application URL</span>
                                <input name="app_url" type="url" value="{{ old('app_url', config('app.url')) }}" required class="mt-2 w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white outline-none ring-emerald-400/40 focus:ring-2">
                            </label>
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between gap-4">
                            <h2 class="text-lg font-semibold text-white">Database</h2>
                            <button formaction="{{ route('install.check') }}" formmethod="post" formnovalidate class="rounded-full border border-white/10 px-3 py-1.5 text-xs font-medium text-zinc-200 transition hover:bg-white/10" type="submit">
                                Test Connection
                            </button>
                        </div>
                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <label class="block">
                                <span class="text-sm text-zinc-300">Host</span>
                                <input name="db_host" value="{{ old('db_host', '127.0.0.1') }}" required class="mt-2 w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white outline-none ring-emerald-400/40 focus:ring-2">
                            </label>
                            <label class="block">
                                <span class="text-sm text-zinc-300">Port</span>
                                <input name="db_port" type="number" min="1" max="65535" value="{{ old('db_port', '3306') }}" required class="mt-2 w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white outline-none ring-emerald-400/40 focus:ring-2">
                            </label>
                            <label class="block">
                                <span class="text-sm text-zinc-300">Database</span>
                                <input name="db_database" value="{{ old('db_database') }}" required class="mt-2 w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white outline-none ring-emerald-400/40 focus:ring-2">
                            </label>
                            <label class="block">
                                <span class="text-sm text-zinc-300">Username</span>
                                <input name="db_username" value="{{ old('db_username') }}" required class="mt-2 w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white outline-none ring-emerald-400/40 focus:ring-2">
                            </label>
                            <label class="block sm:col-span-2">
                                <span class="text-sm text-zinc-300">Password</span>
                                <input name="db_password" type="password" autocomplete="off" class="mt-2 w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white outline-none ring-emerald-400/40 focus:ring-2">
                            </label>
                        </div>
                    </div>

                    <div>
                        <h2 class="text-lg font-semibold text-white">Super Admin</h2>
                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <label class="block">
                                <span class="text-sm text-zinc-300">Name</span>
                                <input name="admin_name" value="{{ old('admin_name') }}" required class="mt-2 w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white outline-none ring-emerald-400/40 focus:ring-2">
                            </label>
                            <label class="block">
                                <span class="text-sm text-zinc-300">Email</span>
                                <input name="admin_email" type="email" value="{{ old('admin_email') }}" required class="mt-2 w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white outline-none ring-emerald-400/40 focus:ring-2">
                            </label>
                            <label class="block">
                                <span class="text-sm text-zinc-300">Password</span>
                                <input name="admin_password" type="password" minlength="12" autocomplete="new-password" required class="mt-2 w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white outline-none ring-emerald-400/40 focus:ring-2">
                            </label>
                            <label class="block">
                                <span class="text-sm text-zinc-300">Confirm password</span>
                                <input name="admin_password_confirmation" type="password" minlength="12" autocomplete="new-password" required class="mt-2 w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white outline-none ring-emerald-400/40 focus:ring-2">
                            </label>
                        </div>
                    </div>

                    <button class="w-full rounded-xl bg-emerald-300 px-4 py-3 text-sm font-semibold text-zinc-950 transition hover:bg-emerald-200" type="submit">
                        Install And Lock
                    </button>
                </form>
            </section>
        </div>
    </main>
</x-layouts.app>
