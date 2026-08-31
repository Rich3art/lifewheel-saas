<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use LifeWheel\Plugins\Gamification\XpCalculator;
use LifeWheel\Plugins\Gamification\XpLedger;

require_once dirname(__DIR__).'/src/XpCalculator.php';
require_once dirname(__DIR__).'/src/XpLedger.php';

Route::middleware(['auth', 'verified', 'twofactor', 'feature:gamification.use'])
    ->prefix('app/xp')
    ->name('plugins.gamification.')
    ->group(function (): void {
        Route::get('/', function (Request $request) {
            $totalXp = app(XpLedger::class)->totalFor($request->user()->id);
            $level = XpCalculator::levelFor($totalXp);
            $nextLevelXp = XpCalculator::nextLevelXp($level);

            return View::file(dirname(__DIR__).'/resources/views/index.blade.php', [
                'totalXp' => $totalXp,
                'level' => $level,
                'nextLevelXp' => $nextLevelXp,
                'events' => DB::table('xp_events')
                    ->where('user_id', $request->user()->id)
                    ->latest()
                    ->limit(30)
                    ->get(),
            ]);
        })->name('index');
    });

Route::middleware(['auth', 'verified', 'twofactor', 'permission:admin.saas.manage'])
    ->prefix('admin/gamification')
    ->name('plugins.gamification.admin.')
    ->group(function (): void {
        Route::get('/', function () {
            return View::file(dirname(__DIR__).'/resources/views/admin.blade.php', [
                'rules' => DB::table('gamification_rules')->orderBy('event_type')->get(),
            ]);
        })->name('index');

        Route::put('/rules/{ruleId}', function (Request $request, int $ruleId) {
            $attributes = $request->validate([
                'xp' => ['required', 'integer', 'min:-10000', 'max:10000'],
                'enabled' => ['nullable', 'boolean'],
                'cooldown_hours' => ['required', 'integer', 'min:0', 'max:8760'],
            ]);

            $updated = DB::table('gamification_rules')
                ->where('id', $ruleId)
                ->update([
                    'xp' => $attributes['xp'],
                    'enabled' => (bool) ($attributes['enabled'] ?? false),
                    'cooldown_hours' => $attributes['cooldown_hours'],
                    'updated_at' => now(),
                ]);

            abort_unless($updated === 1, 404);

            app(\App\Services\AuditLogger::class)->log('gamification.rule_updated', $request->user(), null, [
                'rule_id' => $ruleId,
                'xp' => $attributes['xp'],
                'enabled' => (bool) ($attributes['enabled'] ?? false),
            ]);

            return back()->with('status', 'gamification-rule-updated');
        })->name('rules.update');
    });
