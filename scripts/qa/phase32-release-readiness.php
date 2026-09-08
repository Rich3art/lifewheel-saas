<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$failures = [];

assertFileExists($root, 'scripts/release/package.php', $failures);
assertFileExists($root, '.github/workflows/release.yml', $failures);
assertFileExists($root, 'app/Http/Controllers/InstallController.php', $failures);
assertFileExists($root, 'resources/views/install/show.blade.php', $failures);
assertFileExists($root, 'docs/PHASE_32_CLEAN_INSTALL_UPGRADE_QA.md', $failures);
assertFileExists($root, 'docs/deployment/clean-install-upgrade-qa.md', $failures);

assertContains($root, 'routes/web.php', "Route::get('/install'", $failures);
assertContains($root, 'routes/web.php', "Route::post('/install'", $failures);
assertContains($root, '.env.example', 'APP_INSTALLED=false', $failures);
assertContains($root, '.env.example', 'SESSION_DRIVER=file', $failures);
assertContains($root, '.env.example', 'CACHE_STORE=file', $failures);
assertContains($root, 'scripts/release/package.php', "'.env'", $failures);
assertContains($root, 'scripts/release/package.php', "'storage/app/installed.lock'", $failures);
assertContains($root, 'scripts/release/package.php', "'tests'", $failures);

$plugins = discoverPlugins($root, $failures);

foreach ($plugins as $plugin) {
    $manifest = $plugin['manifest'];
    $directory = $plugin['directory'];

    foreach (['id', 'name', 'version', 'php', 'entry', 'class'] as $key) {
        if (! isset($manifest[$key]) || ! is_string($manifest[$key]) || trim($manifest[$key]) === '') {
            $failures[] = "{$directory}/plugin.json missing required key {$key}.";
        }
    }

    if (isset($manifest['entry']) && ! file_exists($directory.DIRECTORY_SEPARATOR.$manifest['entry'])) {
        $failures[] = "{$manifest['id']} entry file does not exist: {$manifest['entry']}.";
    }
}

if ($failures !== []) {
    echo "Phase 32 release readiness failed:\n";

    foreach ($failures as $failure) {
        echo "- {$failure}\n";
    }

    exit(1);
}

echo "Phase 32 release readiness passed.\n";
echo 'Plugins checked: '.count($plugins)."\n";

/**
 * @param  array<int, string>  $failures
 */
function assertFileExists(string $root, string $relative, array &$failures): void
{
    if (! file_exists($root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative))) {
        $failures[] = "Missing required file: {$relative}.";
    }
}

/**
 * @param  array<int, string>  $failures
 */
function assertContains(string $root, string $relative, string $needle, array &$failures): void
{
    $path = $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);

    if (! file_exists($path)) {
        $failures[] = "Missing required file: {$relative}.";

        return;
    }

    if (! str_contains((string) file_get_contents($path), $needle)) {
        $failures[] = "{$relative} does not contain expected text: {$needle}.";
    }
}

/**
 * @param  array<int, string>  $failures
 * @return array<int, array{directory: string, manifest: array<string, mixed>}>
 */
function discoverPlugins(string $root, array &$failures): array
{
    $pluginRoot = $root.DIRECTORY_SEPARATOR.'plugins';
    $plugins = [];

    if (! is_dir($pluginRoot)) {
        $failures[] = 'Missing plugins directory.';

        return $plugins;
    }

    foreach (new DirectoryIterator($pluginRoot) as $entry) {
        if ($entry->isDot() || ! $entry->isDir()) {
            continue;
        }

        $manifestPath = $entry->getPathname().DIRECTORY_SEPARATOR.'plugin.json';

        if (! file_exists($manifestPath)) {
            $failures[] = $entry->getFilename().' is missing plugin.json.';

            continue;
        }

        $manifest = json_decode((string) file_get_contents($manifestPath), true);

        if (! is_array($manifest)) {
            $failures[] = $entry->getFilename().' has invalid plugin.json.';

            continue;
        }

        $plugins[] = [
            'directory' => $entry->getPathname(),
            'manifest' => $manifest,
        ];
    }

    usort($plugins, fn (array $a, array $b): int => strcmp((string) ($a['manifest']['id'] ?? ''), (string) ($b['manifest']['id'] ?? '')));

    return $plugins;
}
