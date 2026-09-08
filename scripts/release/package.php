<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$version = null;
$dryRun = false;

foreach (array_slice($argv, 1) as $argument) {
    if (str_starts_with($argument, '--version=')) {
        $version = substr($argument, strlen('--version='));
    }

    if ($argument === '--dry-run') {
        $dryRun = true;
    }
}

$version ??= trim((string) shell_exec('git -C '.escapeshellarg($root).' rev-parse --short HEAD 2>NUL')) ?: date('Ymd-His');
$buildDir = $root.DIRECTORY_SEPARATOR.'build';
$releaseDir = $buildDir.DIRECTORY_SEPARATOR.'releases'.DIRECTORY_SEPARATOR.$version;

$coreExcludes = [
    '.git',
    '.github',
    '.env',
    '.env.backup',
    '.phase0-upstream',
    'build',
    'node_modules',
    'storage/app/installed.lock',
    'storage/app/private/exports',
    'storage/framework/cache',
    'storage/framework/sessions',
    'storage/framework/testing',
    'storage/framework/views',
    'storage/logs',
    'tests',
];

$pluginExcludes = [
    '.git',
    'node_modules',
    'tests',
];

if (! class_exists(ZipArchive::class) && ! $dryRun) {
    fwrite(STDERR, "PHP zip extension is required to create release ZIPs.\n");
    exit(1);
}

$plugins = discoverPlugins($root);
$coreFiles = collectFiles($root, $coreExcludes);

if ($dryRun) {
    echo "LifeWheel release dry run for version {$version}\n";
    echo "Core files: ".count($coreFiles)."\n";
    echo "Plugins: ".count($plugins)."\n";

    foreach ($plugins as $plugin) {
        echo "- {$plugin['manifest']['id']} {$plugin['manifest']['version']}\n";
    }

    exit(0);
}

if (! is_dir($releaseDir)) {
    mkdir($releaseDir, 0755, true);
}

createZip($releaseDir.DIRECTORY_SEPARATOR."lifewheel-core-{$version}.zip", $root, $coreFiles);

foreach ($plugins as $plugin) {
    $id = safeArtifactName((string) $plugin['manifest']['id']);
    $pluginVersion = safeArtifactName((string) $plugin['manifest']['version']);
    $files = collectFiles($plugin['path'], $pluginExcludes);

    createZip(
        $releaseDir.DIRECTORY_SEPARATOR."{$id}-plugin-{$pluginVersion}.zip",
        $plugin['path'],
        $files,
    );
}

file_put_contents($releaseDir.DIRECTORY_SEPARATOR.'release-manifest.json', json_encode([
    'version' => $version,
    'created_at' => gmdate(DATE_ATOM),
    'core' => "lifewheel-core-{$version}.zip",
    'plugins' => array_map(fn (array $plugin): array => [
        'id' => $plugin['manifest']['id'],
        'name' => $plugin['manifest']['name'],
        'version' => $plugin['manifest']['version'],
        'artifact' => safeArtifactName((string) $plugin['manifest']['id']).'-plugin-'.safeArtifactName((string) $plugin['manifest']['version']).'.zip',
    ], $plugins),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);

echo "Release artifacts written to {$releaseDir}\n";

/**
 * @return array<int, array{path: string, manifest: array<string, mixed>}>
 */
function discoverPlugins(string $root): array
{
    $pluginRoot = $root.DIRECTORY_SEPARATOR.'plugins';

    if (! is_dir($pluginRoot)) {
        return [];
    }

    $plugins = [];

    foreach (new DirectoryIterator($pluginRoot) as $entry) {
        if ($entry->isDot() || ! $entry->isDir()) {
            continue;
        }

        $manifestPath = $entry->getPathname().DIRECTORY_SEPARATOR.'plugin.json';

        if (! file_exists($manifestPath)) {
            continue;
        }

        $manifest = json_decode((string) file_get_contents($manifestPath), true);

        validateManifest($manifestPath, $manifest);

        $plugins[] = [
            'path' => $entry->getPathname(),
            'manifest' => $manifest,
        ];
    }

    usort($plugins, fn (array $a, array $b): int => strcmp((string) $a['manifest']['id'], (string) $b['manifest']['id']));

    return $plugins;
}

/**
 * @param  mixed  $manifest
 */
function validateManifest(string $path, mixed $manifest): void
{
    if (! is_array($manifest)) {
        fail("Invalid plugin manifest JSON: {$path}");
    }

    foreach (['id', 'name', 'version', 'php', 'entry', 'class'] as $key) {
        if (! isset($manifest[$key]) || ! is_string($manifest[$key]) || trim($manifest[$key]) === '') {
            fail("Plugin manifest {$path} is missing required string key: {$key}");
        }
    }

    if (! preg_match('/^[a-z0-9][a-z0-9._-]*$/', (string) $manifest['id'])) {
        fail("Plugin manifest {$path} has an unsafe id.");
    }
}

/**
 * @param  array<int, string>  $excludePrefixes
 * @return array<int, string>
 */
function collectFiles(string $basePath, array $excludePrefixes): array
{
    $files = [];
    $basePath = realpath($basePath);

    if ($basePath === false) {
        fail('Invalid package path.');
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($basePath, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST,
    );

    foreach ($iterator as $file) {
        if (! $file->isFile()) {
            continue;
        }

        $relative = normalizePath(substr($file->getPathname(), strlen($basePath) + 1));

        if (isExcluded($relative, $excludePrefixes)) {
            continue;
        }

        if (str_contains($relative, '..')) {
            fail("Unsafe relative path detected: {$relative}");
        }

        $files[] = $relative;
    }

    sort($files);

    return $files;
}

/**
 * @param  array<int, string>  $excludePrefixes
 */
function isExcluded(string $relative, array $excludePrefixes): bool
{
    foreach ($excludePrefixes as $exclude) {
        $exclude = normalizePath($exclude);

        if ($relative === $exclude || str_starts_with($relative, $exclude.'/')) {
            return true;
        }
    }

    return false;
}

/**
 * @param  array<int, string>  $files
 */
function createZip(string $target, string $basePath, array $files): void
{
    $zip = new ZipArchive;

    if ($zip->open($target, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        fail("Unable to create ZIP: {$target}");
    }

    foreach ($files as $relative) {
        $absolute = realpath($basePath.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative));

        if ($absolute === false || ! str_starts_with($absolute, realpath($basePath))) {
            $zip->close();
            fail("Unsafe ZIP source path: {$relative}");
        }

        $zip->addFile($absolute, $relative);
    }

    $zip->close();
}

function normalizePath(string $path): string
{
    return str_replace('\\', '/', $path);
}

function safeArtifactName(string $value): string
{
    return preg_replace('/[^A-Za-z0-9._-]/', '-', $value) ?: 'artifact';
}

function fail(string $message): never
{
    fwrite(STDERR, $message.PHP_EOL);
    exit(1);
}
