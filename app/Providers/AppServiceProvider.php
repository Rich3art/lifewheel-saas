<?php

namespace App\Providers;

use App\Models\User;
use App\Plugins\PluginContext;
use App\Plugins\PluginRegistry;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Throwable;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PluginRegistry::class, fn (): PluginRegistry => PluginRegistry::default());
    }

    public function boot(): void
    {
        Gate::before(function (User $user, string $ability): ?bool {
            if (str_starts_with($ability, 'admin.') && $user->hasPermission($ability)) {
                return true;
            }

            return null;
        });

        $this->loadEnabledPluginMigrations();
        $this->loadEnabledPluginRoutes();
    }

    private function loadEnabledPluginMigrations(): void
    {
        try {
            foreach (app(PluginRegistry::class)->enabled() as $plugin) {
                $manifest = $plugin['manifest'];
                $context = new PluginContext($manifest, $plugin['base_path']);

                foreach ($manifest->migrations as $migrationPath) {
                    $path = realpath($context->path($migrationPath));

                    if ($path && is_dir($path)) {
                        $this->loadMigrationsFrom($path);
                    }
                }
            }
        } catch (Throwable) {
            return;
        }
    }

    private function loadEnabledPluginRoutes(): void
    {
        try {
            foreach (app(PluginRegistry::class)->enabled() as $plugin) {
                $manifest = $plugin['manifest'];
                $context = new PluginContext($manifest, $plugin['base_path']);

                foreach ($manifest->routes as $routePath) {
                    $path = realpath($context->path($routePath));
                    $basePath = realpath($context->basePath);

                    if (! $path || ! $basePath || ! str_starts_with($path, $basePath.DIRECTORY_SEPARATOR)) {
                        continue;
                    }

                    Route::middleware('web')->group($path);
                }
            }
        } catch (Throwable) {
            return;
        }
    }
}
