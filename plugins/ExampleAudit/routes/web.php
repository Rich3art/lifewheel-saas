<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'twofactor'])
    ->prefix('plugins/example-audit')
    ->name('plugins.example-audit.')
    ->group(function (): void {
        Route::get('/ping', fn () => response()->json([
            'plugin' => 'example-audit',
            'status' => 'ok',
        ]))->name('ping');
    });
