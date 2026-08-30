<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\PermissionController as AdminPermissionController;
use App\Http\Controllers\Admin\PluginController as AdminPluginController;
use App\Http\Controllers\Admin\RoleController as AdminRoleController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\TwoFactorAuthenticationController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Member\DashboardController as MemberDashboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/health', HealthController::class)->name('health');

Route::middleware('guest')->group(function (): void {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/email/verify', EmailVerificationPromptController::class)->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('/security/2fa/challenge', [TwoFactorAuthenticationController::class, 'challenge'])->name('two-factor.challenge');
    Route::post('/security/2fa/challenge', [TwoFactorAuthenticationController::class, 'verifyChallenge'])
        ->middleware('throttle:6,1')
        ->name('two-factor.verify');
});

Route::middleware(['auth', 'verified', 'twofactor'])->group(function (): void {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'password'])->name('profile.password');

    Route::get('/security/2fa', [TwoFactorAuthenticationController::class, 'show'])->name('two-factor.show');
    Route::post('/security/2fa/confirm', [TwoFactorAuthenticationController::class, 'confirm'])
        ->middleware('throttle:6,1')
        ->name('two-factor.confirm');
    Route::delete('/security/2fa', [TwoFactorAuthenticationController::class, 'disable'])->name('two-factor.disable');
    Route::post('/security/2fa/recovery-codes', [TwoFactorAuthenticationController::class, 'regenerateRecoveryCodes'])
        ->name('two-factor.recovery-codes');
});

Route::prefix('app')
    ->name('member.')
    ->middleware(['auth', 'verified', 'twofactor'])
    ->group(function (): void {
        Route::get('/dashboard', MemberDashboardController::class)->name('dashboard');
    });

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'verified', 'twofactor', 'permission:admin.dashboard.view'])
    ->group(function (): void {
        Route::get('/dashboard', AdminDashboardController::class)->name('dashboard');

        Route::middleware('permission:admin.users.manage')->group(function (): void {
            Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
            Route::get('/users/{user}', [AdminUserController::class, 'edit'])->name('users.edit');
            Route::put('/users/{user}/roles', [AdminUserController::class, 'updateRoles'])->name('users.roles.update');
            Route::put('/users/{user}/permissions', [AdminUserController::class, 'updatePermissions'])->name('users.permissions.update');
            Route::put('/users/{user}/suspend', [AdminUserController::class, 'suspend'])->name('users.suspend');
            Route::put('/users/{user}/unsuspend', [AdminUserController::class, 'unsuspend'])->name('users.unsuspend');
        });

        Route::middleware('permission:admin.roles.manage')->group(function (): void {
            Route::get('/roles', [AdminRoleController::class, 'index'])->name('roles.index');
            Route::post('/roles', [AdminRoleController::class, 'store'])->name('roles.store');
            Route::put('/roles/{role}', [AdminRoleController::class, 'update'])->name('roles.update');
        });

        Route::middleware('permission:admin.permissions.manage')->group(function (): void {
            Route::get('/permissions', [AdminPermissionController::class, 'index'])->name('permissions.index');
            Route::post('/permissions', [AdminPermissionController::class, 'store'])->name('permissions.store');
        });

        Route::middleware('permission:admin.plugins.manage')->group(function (): void {
            Route::get('/plugins', [AdminPluginController::class, 'index'])->name('plugins.index');
            Route::post('/plugins/upload', [AdminPluginController::class, 'upload'])->name('plugins.upload');
            Route::post('/plugins/{pluginId}/install', [AdminPluginController::class, 'install'])->name('plugins.install');
            Route::post('/plugins/{pluginId}/activate', [AdminPluginController::class, 'activate'])->name('plugins.activate');
            Route::post('/plugins/{pluginId}/deactivate', [AdminPluginController::class, 'deactivate'])->name('plugins.deactivate');
            Route::post('/plugins/{pluginId}/update', [AdminPluginController::class, 'update'])->name('plugins.update');
            Route::delete('/plugins/{pluginId}/uninstall', [AdminPluginController::class, 'uninstall'])->name('plugins.uninstall');
            Route::delete('/plugins/{pluginId}/files', [AdminPluginController::class, 'deleteFiles'])->name('plugins.files.delete');
        });
    });
