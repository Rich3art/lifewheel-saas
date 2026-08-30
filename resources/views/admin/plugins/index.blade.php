<x-layouts.app title="Plugins">
    <main class="mx-auto max-w-6xl px-6 py-10">
        <div class="flex flex-col gap-4 border-b border-white/10 pb-8 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="{{ route('admin.dashboard') }}" class="text-sm text-zinc-400">Admin</a>
                <h1 class="mt-2 text-3xl font-semibold">Plugins</h1>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-zinc-400">Install and manage trusted PHP plugins. Uploaded plugins execute server-side code, so only install packages from sources you trust.</p>
            </div>
        </div>

        @if ($errors->any())
            <div class="mt-6 rounded-2xl border border-red-300/20 bg-red-300/10 p-4 text-sm text-red-200">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <section class="mt-8 rounded-2xl border border-white/10 bg-white/[0.03] p-6">
            <h2 class="text-lg font-semibold">Upload plugin ZIP</h2>
            <form method="POST" action="{{ route('admin.plugins.upload') }}" enctype="multipart/form-data" class="mt-5 grid gap-4 sm:grid-cols-[1fr_auto]">
                @csrf
                <input name="plugin_zip" type="file" accept=".zip,application/zip" required class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                <button class="rounded-xl bg-white px-4 py-3 text-sm font-semibold text-zinc-950">Upload and install</button>
            </form>
        </section>

        <section class="mt-8">
            <h2 class="text-lg font-semibold">Discovered plugins</h2>
            <div class="mt-5 grid gap-4">
                @forelse ($discovered as $pluginId => $plugin)
                    @php
                        $manifest = $plugin['manifest'];
                        $record = $installed->get($pluginId);
                    @endphp
                    <article class="rounded-2xl border border-white/10 bg-white/[0.03] p-6">
                        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <div class="flex flex-wrap items-center gap-3">
                                    <h3 class="text-xl font-semibold">{{ $manifest->name }}</h3>
                                    <span class="rounded-full bg-white/10 px-3 py-1 text-xs text-zinc-300">v{{ $manifest->version }}</span>
                                    <span class="rounded-full px-3 py-1 text-xs {{ $record?->isEnabled() ? 'bg-emerald-400/10 text-emerald-200' : 'bg-zinc-500/10 text-zinc-300' }}">
                                        {{ $record?->status ?? 'not installed' }}
                                    </span>
                                </div>
                                <p class="mt-3 max-w-3xl text-sm leading-6 text-zinc-400">{{ $manifest->description }}</p>
                                <dl class="mt-4 grid gap-3 text-sm text-zinc-400 sm:grid-cols-3">
                                    <div>
                                        <dt class="text-zinc-500">Plugin ID</dt>
                                        <dd class="text-zinc-200">{{ $manifest->id }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-zinc-500">Author</dt>
                                        <dd class="text-zinc-200">{{ $manifest->author }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-zinc-500">Requires</dt>
                                        <dd class="text-zinc-200">PHP {{ $manifest->php }}, Core {{ $manifest->coreVersion }}</dd>
                                    </div>
                                </dl>
                            </div>

                            <div class="grid gap-3 sm:min-w-64">
                                @if (! $record)
                                    <form method="POST" action="{{ route('admin.plugins.install', $manifest->id) }}">
                                        @csrf
                                        <button class="w-full rounded-xl bg-white px-4 py-3 text-sm font-semibold text-zinc-950">Install</button>
                                    </form>
                                @elseif (! $record->isEnabled())
                                    <form method="POST" action="{{ route('admin.plugins.activate', $manifest->id) }}">
                                        @csrf
                                        <button class="w-full rounded-xl bg-emerald-300 px-4 py-3 text-sm font-semibold text-emerald-950">Enable</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.plugins.deactivate', $manifest->id) }}">
                                        @csrf
                                        <button class="w-full rounded-xl border border-white/10 px-4 py-3 text-sm text-zinc-200">Disable</button>
                                    </form>
                                @endif

                                @if ($record)
                                    <form method="POST" action="{{ route('admin.plugins.update', $manifest->id) }}" enctype="multipart/form-data" class="grid gap-2">
                                        @csrf
                                        <input name="plugin_zip" type="file" accept=".zip,application/zip" required class="rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-xs">
                                        <button class="rounded-xl border border-white/10 px-4 py-2 text-sm text-zinc-200">Update from ZIP</button>
                                    </form>

                                    <form method="POST" action="{{ route('admin.plugins.uninstall', $manifest->id) }}" class="grid gap-2">
                                        @csrf
                                        @method('DELETE')
                                        <input name="confirmation" placeholder="Type UNINSTALL" class="rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-xs">
                                        <label class="flex items-center gap-2 text-xs text-zinc-400">
                                            <input name="remove_data" value="1" type="checkbox">
                                            Remove plugin data
                                        </label>
                                        <button class="rounded-xl bg-red-300 px-4 py-2 text-sm font-semibold text-red-950">Uninstall</button>
                                    </form>

                                    <form method="POST" action="{{ route('admin.plugins.files.delete', $manifest->id) }}" class="grid gap-2">
                                        @csrf
                                        @method('DELETE')
                                        <input name="confirmation" placeholder="Type DELETE FILES" class="rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-xs">
                                        <button class="rounded-xl border border-red-300/30 px-4 py-2 text-sm text-red-200">Delete files</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-6 text-sm text-zinc-400">No plugin manifests were found.</div>
                @endforelse
            </div>
        </section>
    </main>
</x-layouts.app>
