<x-layouts.app title="AI Settings">
    <main class="mx-auto max-w-6xl px-6 py-10">
        <a href="{{ route('admin.dashboard') }}" class="text-sm text-zinc-400">Admin</a>
        <h1 class="mt-2 text-3xl font-semibold">AI settings</h1>

        <section class="mt-8 rounded-2xl border {{ $lifeWheelAiReady ? 'border-emerald-400/20 bg-emerald-400/10' : 'border-amber-400/20 bg-amber-400/10' }} p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p class="text-sm {{ $lifeWheelAiReady ? 'text-emerald-200' : 'text-amber-100' }}">LifeWheel AI status</p>
                    <h2 class="mt-1 text-xl font-semibold">{{ $lifeWheelAiReady ? 'OpenAI is ready for LifeWheel feedback' : 'OpenAI is not fully connected yet' }}</h2>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-zinc-300">
                        New LifeWheel submissions use the AI Coach route. When this status is ready, reports will show “Generated with OpenAI”; otherwise they use the local fallback.
                    </p>
                </div>
                <div class="rounded-xl border border-white/10 bg-black/10 px-4 py-3 text-sm">
                    <div class="text-zinc-400">AI Coach route</div>
                    <div class="mt-1 font-semibold">{{ $coachRoute?->provider?->name ?? 'No provider' }} / {{ $coachRoute?->model ?? 'No model' }}</div>
                </div>
            </div>
            <div class="mt-5 grid gap-3 md:grid-cols-4">
                <div class="rounded-xl border border-white/10 bg-white/[0.03] px-4 py-3 text-sm">
                    <div class="text-zinc-400">OpenAI enabled</div>
                    <div class="mt-1 font-semibold">{{ $openAiProvider?->enabled ? 'Yes' : 'No' }}</div>
                </div>
                <div class="rounded-xl border border-white/10 bg-white/[0.03] px-4 py-3 text-sm">
                    <div class="text-zinc-400">Mock mode</div>
                    <div class="mt-1 font-semibold">{{ $openAiProvider?->mock_mode ? 'On' : 'Off' }}</div>
                </div>
                <div class="rounded-xl border border-white/10 bg-white/[0.03] px-4 py-3 text-sm">
                    <div class="text-zinc-400">API key saved</div>
                    <div class="mt-1 font-semibold">{{ $openAiProvider?->encrypted_api_key ? 'Yes' : 'No' }}</div>
                </div>
                <div class="rounded-xl border border-white/10 bg-white/[0.03] px-4 py-3 text-sm">
                    <div class="text-zinc-400">Route active</div>
                    <div class="mt-1 font-semibold">{{ ($coachRoute?->enabled && $coachRoute?->provider?->key === 'openai') ? 'Yes' : 'No' }}</div>
                </div>
            </div>
        </section>

        <section class="mt-8 rounded-2xl border border-white/10 bg-white/[0.03] p-6">
            <h2 class="text-lg font-semibold">LifeWheel feedback prompt</h2>
            <p class="mt-2 max-w-3xl text-sm text-zinc-400">
                This prompt is read before every new LifeWheel submission. It controls the tone and coaching rules used when the app compares current and previous LifeWheel notes.
            </p>
            <form method="POST" action="{{ route('admin.ai.prompts.update') }}" class="mt-4 space-y-4">
                @csrf
                @method('PUT')
                <input type="hidden" name="key" value="lifewheel.feedback.system_prompt">
                <input type="hidden" name="label" value="LifeWheel AI Coach Feedback">
                <textarea name="prompt" rows="14" class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm leading-6 text-zinc-100">{{ old('prompt', $lifeWheelPrompt?->prompt ?? '') }}</textarea>
                <button class="rounded-xl bg-white px-4 py-2 text-sm font-semibold text-zinc-950">Save LifeWheel prompt</button>
            </form>
        </section>

        <section class="mt-6 rounded-2xl border border-white/10 bg-white/[0.03] p-6">
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
