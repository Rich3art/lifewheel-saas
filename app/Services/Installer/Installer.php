<?php

namespace App\Services\Installer;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final class Installer
{
    public function __construct(
        private readonly EnvWriter $envWriter,
        private readonly InstallationState $installationState,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function install(array $data): User
    {
        $appKey = config('app.key') ?: 'base64:'.base64_encode(random_bytes(32));

        $this->envWriter->write([
            'APP_NAME' => (string) $data['site_name'],
            'APP_ENV' => 'production',
            'APP_KEY' => $appKey,
            'APP_DEBUG' => 'false',
            'APP_URL' => (string) $data['app_url'],
            'APP_TIMEZONE' => (string) $data['timezone'],
            'LIFEOS_BOOTSTRAP_ADMIN_EMAIL' => (string) $data['admin_email'],
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => (string) $data['db_host'],
            'DB_PORT' => (string) $data['db_port'],
            'DB_DATABASE' => (string) $data['db_database'],
            'DB_USERNAME' => (string) $data['db_username'],
            'DB_PASSWORD' => (string) ($data['db_password'] ?? ''),
            'SESSION_DRIVER' => 'database',
            'SESSION_ENCRYPT' => 'true',
            'SESSION_SECURE_COOKIE' => str_starts_with((string) $data['app_url'], 'https://') ? 'true' : 'false',
            'CACHE_STORE' => 'database',
            'QUEUE_CONNECTION' => 'database',
            'APP_INSTALLED' => 'true',
        ]);

        config([
            'app.name' => (string) $data['site_name'],
            'app.key' => $appKey,
            'app.url' => (string) $data['app_url'],
            'database.default' => 'mysql',
            'database.connections.mysql.host' => (string) $data['db_host'],
            'database.connections.mysql.port' => (string) $data['db_port'],
            'database.connections.mysql.database' => (string) $data['db_database'],
            'database.connections.mysql.username' => (string) $data['db_username'],
            'database.connections.mysql.password' => (string) ($data['db_password'] ?? ''),
        ]);

        DB::purge('mysql');

        Artisan::call('config:clear');
        Artisan::call('migrate', ['--force' => true]);
        Artisan::call('db:seed', ['--force' => true]);

        $admin = User::query()->updateOrCreate(
            ['email' => (string) $data['admin_email']],
            [
                'name' => (string) $data['admin_name'],
                'password' => Hash::make((string) $data['admin_password']),
                'email_verified_at' => now(),
                'timezone' => (string) $data['timezone'],
            ],
        );

        $superAdminRole = Role::query()->where('slug', 'super-admin')->firstOrFail();
        $admin->roles()->syncWithoutDetaching([$superAdminRole->id]);

        $this->installationState->writeLock([
            'app_url' => (string) $data['app_url'],
            'site_name' => (string) $data['site_name'],
        ]);

        return $admin;
    }
}
