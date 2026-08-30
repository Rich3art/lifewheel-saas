<?php

namespace App\Plugins;

use InvalidArgumentException;

final readonly class PluginManifest
{
    public function __construct(
        public string $id,
        public string $name,
        public string $version,
        public string $author,
        public string $description,
        public string $coreVersion,
        public string $php,
        public string $entry,
        public array $dependencies,
        public array $permissions,
        public array $features,
        public array $menus,
        public array $settingsSections,
        public array $routes,
        public array $migrations,
        public array $raw,
    ) {
    }

    public static function fromArray(array $data): self
    {
        foreach (['id', 'name', 'version', 'author', 'description', 'core_version', 'php', 'entry'] as $key) {
            if (! isset($data[$key]) || ! is_string($data[$key]) || trim($data[$key]) === '') {
                throw new InvalidArgumentException("Plugin manifest is missing required string field [{$key}].");
            }
        }

        if (! preg_match('/^[a-z0-9][a-z0-9._-]*$/', $data['id'])) {
            throw new InvalidArgumentException('Plugin manifest id must be lowercase letters, numbers, dots, underscores, or hyphens.');
        }

        if (! str_starts_with($data['entry'], 'src/')) {
            throw new InvalidArgumentException('Plugin entry must live inside the plugin src directory.');
        }

        return new self(
            id: $data['id'],
            name: $data['name'],
            version: $data['version'],
            author: $data['author'],
            description: $data['description'],
            coreVersion: $data['core_version'],
            php: $data['php'],
            entry: $data['entry'],
            dependencies: self::list($data['dependencies'] ?? []),
            permissions: self::list($data['permissions'] ?? []),
            features: self::list($data['features'] ?? []),
            menus: self::list($data['menus'] ?? []),
            settingsSections: self::list($data['settings_sections'] ?? []),
            routes: self::list($data['routes'] ?? []),
            migrations: self::list($data['migrations'] ?? []),
            raw: $data,
        );
    }

    private static function list(mixed $value): array
    {
        if (! is_array($value)) {
            throw new InvalidArgumentException('Plugin manifest list fields must be arrays.');
        }

        return $value;
    }
}
