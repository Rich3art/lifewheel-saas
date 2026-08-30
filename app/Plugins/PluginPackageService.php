<?php

namespace App\Plugins;

use App\Services\AuditLogger;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use RuntimeException;
use ZipArchive;

final class PluginPackageService
{
    private const ALLOWED_EXTENSIONS = [
        'css',
        'js',
        'json',
        'md',
        'php',
        'png',
        'svg',
        'txt',
        'webp',
    ];

    private const DENIED_SEGMENTS = [
        '.env',
        '.git',
        'vendor',
        'node_modules',
    ];

    public function __construct(
        private readonly AuditLogger $audit,
    ) {
    }

    public function upload(UploadedFile $file): PluginManifest
    {
        if ($file->getClientOriginalExtension() !== 'zip') {
            throw new RuntimeException('Plugin packages must be ZIP files.');
        }

        $maxBytes = (int) config('plugins.max_upload_megabytes', 10) * 1024 * 1024;

        if ($file->getSize() > $maxBytes) {
            throw new RuntimeException('Plugin package exceeds the configured upload size limit.');
        }

        $zip = new ZipArchive();

        if ($zip->open($file->getRealPath()) !== true) {
            throw new RuntimeException('Plugin package could not be opened.');
        }

        $this->validateZip($zip);

        $manifest = $this->manifestFromZip($zip);
        $target = $this->pluginTargetPath($manifest->id);
        $this->ensureTargetIsInsidePluginDirectory($target);

        if (is_dir($target)) {
            throw new RuntimeException('A plugin with this id already exists. Use the update action instead.');
        }

        File::ensureDirectoryExists($target);
        $zip->extractTo($target);
        $zip->close();

        $this->audit->log('plugin.package_uploaded', request()->user(), null, ['plugin_id' => $manifest->id]);

        return $manifest;
    }

    public function updatePackage(UploadedFile $file, string $pluginId): PluginManifest
    {
        if ($file->getClientOriginalExtension() !== 'zip') {
            throw new RuntimeException('Plugin packages must be ZIP files.');
        }

        $zip = new ZipArchive();

        if ($zip->open($file->getRealPath()) !== true) {
            throw new RuntimeException('Plugin package could not be opened.');
        }

        $this->validateZip($zip);
        $manifest = $this->manifestFromZip($zip);

        if ($manifest->id !== $pluginId) {
            throw new RuntimeException('Uploaded plugin id does not match the plugin being updated.');
        }

        $target = $this->pluginTargetPath($manifest->id);
        $this->ensureTargetIsInsidePluginDirectory($target);

        if (! is_dir($target)) {
            throw new RuntimeException('Cannot update a plugin that is not present on disk.');
        }

        $backup = $target.'.backup-'.now()->format('YmdHis');
        File::move($target, $backup);

        try {
            File::ensureDirectoryExists($target);
            $zip->extractTo($target);
        } catch (RuntimeException $exception) {
            File::deleteDirectory($target);
            File::move($backup, $target);

            throw $exception;
        } finally {
            $zip->close();
        }

        File::deleteDirectory($backup);
        $this->audit->log('plugin.package_updated', request()->user(), null, ['plugin_id' => $manifest->id]);

        return $manifest;
    }

    public function deleteFiles(string $pluginId): void
    {
        $target = $this->pluginTargetPath($pluginId);
        $this->ensureTargetIsInsidePluginDirectory($target);

        if (! is_dir($target)) {
            return;
        }

        File::deleteDirectory($target);
        $this->audit->log('plugin.files_deleted', request()->user(), null, ['plugin_id' => $pluginId]);
    }

    private function validateZip(ZipArchive $zip): void
    {
        $hasManifest = false;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string) $zip->getNameIndex($i);
            $normalized = str_replace('\\', '/', $name);

            if ($normalized === 'plugin.json') {
                $hasManifest = true;
            }

            if (str_contains($normalized, '../') || str_starts_with($normalized, '/') || preg_match('/^[a-zA-Z]:\//', $normalized)) {
                throw new RuntimeException('Plugin ZIP contains an unsafe path.');
            }

            $segments = array_filter(explode('/', $normalized));

            foreach ($segments as $segment) {
                if (in_array($segment, self::DENIED_SEGMENTS, true)) {
                    throw new RuntimeException('Plugin ZIP contains a denied directory or file.');
                }
            }

            if (! str_ends_with($normalized, '/')) {
                $extension = strtolower(pathinfo($normalized, PATHINFO_EXTENSION));

                if (! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
                    throw new RuntimeException("Plugin ZIP contains a disallowed file type [{$extension}].");
                }
            }
        }

        if (! $hasManifest) {
            throw new RuntimeException('Plugin ZIP must contain plugin.json at the package root.');
        }
    }

    private function manifestFromZip(ZipArchive $zip): PluginManifest
    {
        $raw = $zip->getFromName('plugin.json');

        if (! is_string($raw)) {
            throw new RuntimeException('Plugin ZIP must contain plugin.json at the package root.');
        }

        return PluginManifest::fromArray(json_decode($raw, true, flags: JSON_THROW_ON_ERROR));
    }

    private function pluginTargetPath(string $pluginId): string
    {
        return rtrim((string) config('plugins.path', base_path('plugins')), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$pluginId;
    }

    private function ensureTargetIsInsidePluginDirectory(string $target): void
    {
        $root = realpath((string) config('plugins.path', base_path('plugins'))) ?: (string) config('plugins.path', base_path('plugins'));
        File::ensureDirectoryExists($root);
        $root = realpath($root);
        $parent = realpath(dirname($target)) ?: dirname($target);

        if (! $root || ! str_starts_with($parent, $root)) {
            throw new RuntimeException('Plugin target path is outside the configured plugin directory.');
        }
    }
}
