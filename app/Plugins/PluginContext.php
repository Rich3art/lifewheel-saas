<?php

namespace App\Plugins;

final readonly class PluginContext
{
    public function __construct(
        public PluginManifest $manifest,
        public string $basePath,
    ) {
    }

    public function path(string $relativePath = ''): string
    {
        return rtrim($this->basePath.DIRECTORY_SEPARATOR.ltrim($relativePath, DIRECTORY_SEPARATOR), DIRECTORY_SEPARATOR);
    }
}
