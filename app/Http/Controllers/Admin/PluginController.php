<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InstalledPlugin;
use App\Plugins\PluginLifecycleService;
use App\Plugins\PluginPackageService;
use App\Plugins\PluginRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

final class PluginController extends Controller
{
    public function index(PluginRegistry $registry): View
    {
        $installed = InstalledPlugin::query()
            ->with(['features', 'permissions', 'menus', 'settingsSections'])
            ->orderBy('name')
            ->get()
            ->keyBy('plugin_id');

        return view('admin.plugins.index', [
            'discovered' => $registry->discover(),
            'installed' => $installed,
        ]);
    }

    public function upload(Request $request, PluginPackageService $packages, PluginLifecycleService $lifecycle): RedirectResponse
    {
        $attributes = $request->validate([
            'plugin_zip' => ['required', 'file', 'mimes:zip', 'max:'.((int) config('plugins.max_upload_megabytes', 10) * 1024)],
        ]);

        try {
            $manifest = $packages->upload($attributes['plugin_zip']);
            $lifecycle->install($manifest->id);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['plugin_zip' => $exception->getMessage()]);
        }

        return back()->with('status', 'plugin-uploaded');
    }

    public function install(string $pluginId, PluginLifecycleService $lifecycle): RedirectResponse
    {
        return $this->run(fn () => $lifecycle->install($pluginId), 'plugin-installed');
    }

    public function activate(string $pluginId, PluginLifecycleService $lifecycle): RedirectResponse
    {
        return $this->run(fn () => $lifecycle->activate($pluginId), 'plugin-activated');
    }

    public function deactivate(string $pluginId, PluginLifecycleService $lifecycle): RedirectResponse
    {
        return $this->run(fn () => $lifecycle->deactivate($pluginId), 'plugin-deactivated');
    }

    public function update(Request $request, string $pluginId, PluginPackageService $packages, PluginLifecycleService $lifecycle): RedirectResponse
    {
        $attributes = $request->validate([
            'plugin_zip' => ['required', 'file', 'mimes:zip', 'max:'.((int) config('plugins.max_upload_megabytes', 10) * 1024)],
        ]);

        try {
            $packages->updatePackage($attributes['plugin_zip'], $pluginId);
            $lifecycle->upgrade($pluginId);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['plugin_zip' => $exception->getMessage()]);
        }

        return back()->with('status', 'plugin-updated');
    }

    public function uninstall(Request $request, string $pluginId, PluginLifecycleService $lifecycle): RedirectResponse
    {
        $attributes = $request->validate([
            'confirmation' => ['required', 'in:UNINSTALL'],
            'remove_data' => ['nullable', 'boolean'],
        ]);

        return $this->run(fn () => $lifecycle->uninstall($pluginId, (bool) ($attributes['remove_data'] ?? false)), 'plugin-uninstalled');
    }

    public function deleteFiles(Request $request, string $pluginId, PluginPackageService $packages): RedirectResponse
    {
        $request->validate([
            'confirmation' => ['required', 'in:DELETE FILES'],
        ]);

        $installed = InstalledPlugin::query()->find($pluginId);

        if ($installed) {
            return back()->withErrors(['plugin' => 'Uninstall the plugin before deleting plugin files.']);
        }

        try {
            $packages->deleteFiles($pluginId);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['plugin' => $exception->getMessage()]);
        }

        return back()->with('status', 'plugin-files-deleted');
    }

    private function run(callable $callback, string $status): RedirectResponse
    {
        try {
            $callback();
        } catch (RuntimeException $exception) {
            return back()->withErrors(['plugin' => $exception->getMessage()]);
        }

        return back()->with('status', $status);
    }
}
