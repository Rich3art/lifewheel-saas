<?php

namespace App\Plugins\Contracts;

use App\Plugins\PluginContext;

interface Plugin
{
    public function register(PluginContext $context): void;

    public function boot(PluginContext $context): void;

    public function install(PluginContext $context): void;

    public function activate(PluginContext $context): void;

    public function deactivate(PluginContext $context): void;

    public function upgrade(PluginContext $context, string $fromVersion): void;

    public function uninstall(PluginContext $context, bool $removeData): void;
}
