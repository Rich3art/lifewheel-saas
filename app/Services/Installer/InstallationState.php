<?php

namespace App\Services\Installer;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class InstallationState
{
    public function isLocked(): bool
    {
        if ((bool) config('installer.locked', false)) {
            return true;
        }

        if (file_exists($this->lockPath())) {
            return true;
        }

        return $this->databaseHasUsers();
    }

    public function lockPath(): string
    {
        return storage_path('app/installed.lock');
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function writeLock(array $metadata): void
    {
        $directory = dirname($this->lockPath());

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($this->lockPath(), json_encode([
            'installed_at' => now()->toIso8601String(),
            'app_url' => $metadata['app_url'] ?? config('app.url'),
            'site_name' => $metadata['site_name'] ?? config('app.name'),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    private function databaseHasUsers(): bool
    {
        try {
            DB::connection()->getPdo();

            return Schema::hasTable((new User)->getTable()) && User::query()->exists();
        } catch (\Throwable) {
            return false;
        }
    }
}
