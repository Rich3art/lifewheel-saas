<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::before(function (User $user, string $ability): ?bool {
            if (str_starts_with($ability, 'admin.') && $user->hasPermission($ability)) {
                return true;
            }

            return null;
        });
    }
}
