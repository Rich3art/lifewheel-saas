<x-layouts.app title="Features">
    <main class="mx-auto max-w-5xl px-6 py-10">
        <div class="flex items-center justify-between border-b border-white/10 pb-8">
            <div>
                <a href="{{ route('admin.dashboard') }}" class="text-sm text-zinc-400">Admin</a>
                <h1 class="mt-2 text-3xl font-semibold">Features</h1>
            </div>
            <a href="{{ route('admin.packages.index') }}" class="rounded-xl border border-white/10 px-4 py-2 text-sm">Packages</a>
        </div>

        <section class="mt-8 rounded-2xl border border-white/10 bg-white/[0.03] p-6">
            <h2 class="text-lg font-semibold">Create feature</h2>
            <form method="POST" action="{{ route('admin.features.store') }}" class="mt-5 grid gap-4 lg:grid-cols-[1fr_1fr_auto]">
                @csrf
                <input name="name" placeholder="Feature name" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                <input name="slug" placeholder="feature.slug" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                <button class="rounded-xl bg-white px-4 py-3 text-sm font-semibold text-zinc-950">Create</button>
            </form>
        </section>

        <div class="mt-8 grid gap-4">
            @foreach ($features as $feature)
                <form method="POST" action="{{ route('admin.features.update', $feature) }}" class="rounded-2xl border border-white/10 bg-white/[0.03] p-6">
                    @csrf
                    @method('PUT')
                    <div class="grid gap-4 lg:grid-cols-[1fr_1fr_auto]">
                        <input name="name" value="{{ $feature->name }}" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                        <input value="{{ $feature->slug }}" disabled class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-zinc-400">
                        <label class="flex items-center gap-2 text-sm text-zinc-300"><input type="checkbox" name="active" value="1" @checked($feature->active)> Active</label>
                    </div>
                    <textarea name="description" rows="2" class="mt-4 w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">{{ $feature->description }}</textarea>
                    <button class="mt-4 rounded-xl bg-white px-4 py-3 text-sm font-semibold text-zinc-950">Save</button>
                </form>
            @endforeach
        </div>
        <div class="mt-6">{{ $features->links() }}</div>
    </main>
</x-layouts.app>
