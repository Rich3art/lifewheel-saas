<?php

namespace App\Services\Installer;

use PDO;
use Throwable;

final class RequirementChecker
{
    /**
     * @return array<int, array{name: string, ok: bool, required: bool, detail: string}>
     */
    public function check(): array
    {
        return [
            $this->phpVersion('8.2.0'),
            ...array_map(fn (string $extension): array => $this->extension($extension), [
                'ctype',
                'curl',
                'dom',
                'fileinfo',
                'filter',
                'hash',
                'json',
                'mbstring',
                'openssl',
                'pdo',
                'pdo_mysql',
                'session',
                'tokenizer',
                'xml',
                'zip',
            ]),
            $this->writable('Storage directory', storage_path()),
            $this->writable('Bootstrap cache directory', base_path('bootstrap/cache')),
            $this->envWritable(),
        ];
    }

    /**
     * @param  array{host: string, port: int|string, database: string, username: string, password?: string|null}  $database
     * @return array{ok: bool, detail: string}
     */
    public function checkDatabase(array $database): array
    {
        try {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $database['host'],
                $database['port'],
                $database['database'],
            );

            new PDO($dsn, $database['username'], (string) ($database['password'] ?? ''), [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5,
            ]);

            return ['ok' => true, 'detail' => 'Database connection succeeded.'];
        } catch (Throwable $exception) {
            return ['ok' => false, 'detail' => 'Database connection failed: '.$exception->getMessage()];
        }
    }

    public function hasBlockingFailures(): bool
    {
        return collect($this->check())->contains(fn (array $check): bool => $check['required'] && ! $check['ok']);
    }

    /**
     * @return array{name: string, ok: bool, required: bool, detail: string}
     */
    private function phpVersion(string $minimum): array
    {
        return [
            'name' => 'PHP '.$minimum.'+',
            'ok' => version_compare(PHP_VERSION, $minimum, '>='),
            'required' => true,
            'detail' => 'Current version: '.PHP_VERSION,
        ];
    }

    /**
     * @return array{name: string, ok: bool, required: bool, detail: string}
     */
    private function extension(string $extension): array
    {
        return [
            'name' => 'Extension: '.$extension,
            'ok' => extension_loaded($extension),
            'required' => true,
            'detail' => extension_loaded($extension) ? 'Loaded' : 'Missing',
        ];
    }

    /**
     * @return array{name: string, ok: bool, required: bool, detail: string}
     */
    private function writable(string $name, string $path): array
    {
        return [
            'name' => $name,
            'ok' => is_dir($path) && is_writable($path),
            'required' => true,
            'detail' => $path,
        ];
    }

    /**
     * @return array{name: string, ok: bool, required: bool, detail: string}
     */
    private function envWritable(): array
    {
        $envPath = base_path('.env');
        $target = file_exists($envPath) ? $envPath : base_path();

        return [
            'name' => '.env writable',
            'ok' => is_writable($target),
            'required' => true,
            'detail' => file_exists($envPath) ? $envPath : 'Application root can create .env',
        ];
    }
}
