<x-layouts.app title="Packages">
    <main class="mx-auto max-w-6xl px-6 py-10">
        <div class="flex items-center justify-between border-b border-white/10 pb-8">
            <div>
                <a href="{{ route('admin.dashboard') }}" class="text-sm text-zinc-400">Admin</a>
                <h1 class="mt-2 text-3xl font-semibold">Packages</h1>
            </div>
            <a href="{{ route('admin.features.index') }}" class="rounded-xl border border-white/10 px-4 py-2 text-sm">Features</a>
        </div>

        <section class="mt-8 rounded-2xl border border-white/10 bg-white/[0.03] p-6">
            <h2 class="text-lg font-semibold">Create package</h2>
            <form method="POST" action="{{ route('admin.packages.store') }}" class="mt-5 grid gap-4 lg:grid-cols-4">
                @csrf
                <input name="name" placeholder="Name" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                <input name="slug" placeholder="slug" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                <input name="price_cents" value="0" type="number" min="0" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                <button class="rounded-xl bg-white px-4 py-3 text-sm font-semibold text-zinc-950">Create</button>
                <input type="hidden" name="currency" value="USD">
                <input type="hidden" name="billing_interval" value="monthly">
                <input type="hidden" name="trial_days" value="0">
                <input type="hidden" name="sort_order" value="100">
            </form>
        </section>

        <div class="mt-8 grid gap-5">
            @foreach ($packages as $package)
                <form method="POST" action="{{ route('admin.packages.update', $package) }}" class="rounded-2xl border border-white/10 bg-white/[0.03] p-6">
                    @csrf
                    @method('PUT')
                    <div class="grid gap-4 lg:grid-cols-4">
                        <input name="name" value="{{ $package->name }}" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                        <input name="slug" value="{{ $package->slug }}" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                        <input name="price_cents" value="{{ $package->price_cents }}" type="number" min="0" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                        <select name="billing_interval" class="rounded-xl border border-white/10 bg-zinc-900 px-4 py-3 text-sm">
                            @foreach (['monthly', 'quarterly', 'yearly', 'lifetime'] as $interval)
                                <option value="{{ $interval }}" @selected($package->billing_interval === $interval)>{{ ucfirst($interval) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mt-4 grid gap-4 lg:grid-cols-4">
                        <input name="currency" value="{{ $package->currency }}" maxlength="3" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                        <input name="trial_days" value="{{ $package->trial_days }}" type="number" min="0" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                        <input name="sort_order" value="{{ $package->sort_order }}" type="number" min="0" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                        <input name="landing_page_slug" value="{{ $package->landing_page_slug }}" placeholder="landing slug" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                    </div>
                    <input name="short_description" value="{{ $package->short_description }}" placeholder="Short description" class="mt-4 w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                    <textarea name="description" rows="2" class="mt-4 w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">{{ $package->description }}</textarea>
                    <div class="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ($features as $feature)
                            <label class="flex items-center gap-2 text-sm text-zinc-300">
                                <input type="checkbox" name="features[]" value="{{ $feature->id }}" @checked($package->features->contains($feature))>
                                {{ $feature->slug }}
                            </label>
                        @endforeach
                    </div>
                    <textarea name="limits" rows="3" placeholder="limit.key=value" class="mt-4 w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">{{ $package->limits->map(fn ($limit) => $limit->key.'='.$limit->value)->join("\n") }}</textarea>
                    <div class="mt-4 flex flex-wrap items-center gap-4">
                        <label class="flex items-center gap-2 text-sm text-zinc-300"><input type="checkbox" name="active" value="1" @checked($package->active)> Active</label>
                        <label class="flex items-center gap-2 text-sm text-zinc-300"><input type="checkbox" name="public" value="1" @checked($package->public)> Public</label>
                        <label class="flex items-center gap-2 text-sm text-zinc-300"><input type="checkbox" name="featured" value="1" @checked($package->featured)> Featured</label>
                        <button class="rounded-xl bg-white px-4 py-3 text-sm font-semibold text-zinc-950">Save package</button>
                    </div>
                </form>
                <form method="POST" action="{{ route('admin.packages.duplicate', $package) }}">
                    @csrf
                    <button class="text-sm text-zinc-400 underline">Duplicate {{ $package->name }}</button>
                </form>
            @endforeach
        </div>
    </main>
</x-layouts.app>
