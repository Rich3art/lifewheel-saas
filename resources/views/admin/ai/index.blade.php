<x-layouts.app title="AI Settings">
    <main class="mx-auto max-w-6xl px-6 py-10">
        <a href="{{ route('admin.dashboard') }}" class="text-sm text-zinc-400">Admin</a>
        <h1 class="mt-2 text-3xl font-semibold">AI settings</h1>

        <section class="mt-8 rounded-2xl border border-white/10 bg-white/[0.03] p-6">
            <h2 class="text-lg font-semibold">Providers</h2>
            <div class="mt-4 space-y-4">
                @foreach ($providers as $provider)
                    <form method="POST" action="{{ route('admin.ai.providers.update', $provider) }}" class="grid gap-3 rounded-xl border border-white/10 p-4 lg:grid-cols-[1fr_1fr_1fr_120px_120px] lg:items-center">
                        @csrf
                        @method('PUT')
                        <div>
                            <p class="text-sm font-semibold">{{ $provider->key }}</p>
                            <input name="name" value="{{ $provider->name }}" class="mt-2 w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm">
                        </div>
                        <input name="base_url" value="{{ $provider->base_url }}" placeholder="Base URL" class="rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm">
                        <input name="api_key" type="password" placeholder="Replace API key" class="rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm">
                        <label class="flex items-center gap-2 text-sm text-zinc-300"><input name="enabled" type="checkbox" value="1" @checked($provider->enabled)> Enabled</label>
                        <label class="flex items-center gap-2 text-sm text-zinc-300"><input name="mock_mode" type="checkbox" value="1" @checked($provider->mock_mode)> Mock</label>
                        <button class="rounded-xl bg-white px-4 py-2 text-sm font-semibold text-zinc-950 lg:col-start-5">Save</button>
                    </form>
                @endforeach
            </div>
        </section>

        <section class="mt-6 rounded-2xl border border-white/10 bg-white/[0.03] p-6">
            <h2 class="text-lg font-semibold">Model routing</h2>
            <div class="mt-4 space-y-4">
                @foreach ($routes as $route)
                    <form method="POST" action="{{ route('admin.ai.routes.update', $route) }}" class="grid gap-3 rounded-xl border border-white/10 p-4 lg:grid-cols-[1fr_1fr_1fr_120px_120px] lg:items-center">
                        @csrf
                        @method('PUT')
                        <div>
                            <p class="text-sm font-semibold">{{ $route->feature_slug }}</p>
                            <p class="mt-1 text-xs text-zinc-500">Route #{{ $route->id }}</p>
                        </div>
                        <select name="ai_provider_id" class="rounded-xl border border-white/10 bg-zinc-900 px-3 py-2 text-sm">
                            <option value="">None</option>
                            @foreach ($providers as $provider)
                                <option value="{{ $provider->id }}" @selected($route->ai_provider_id === $provider->id)>{{ $provider->name }}</option>
                            @endforeach
                        </select>
                        <input name="model" value="{{ $route->model }}" class="rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm">
                        <input name="monthly_limit" type="number" min="0" value="{{ $route->monthly_limit }}" placeholder="Monthly" class="rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm">
                        <label class="flex items-center gap-2 text-sm text-zinc-300"><input name="enabled" type="checkbox" value="1" @checked($route->enabled)> Enabled</label>
                        <input name="sort_order" type="number" min="0" value="{{ $route->sort_order }}" class="rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm">
                        <button class="rounded-xl bg-white px-4 py-2 text-sm font-semibold text-zinc-950 lg:col-start-5">Save</button>
                    </form>
                @endforeach
            </div>
        </section>
    </main>
</x-layouts.app>
