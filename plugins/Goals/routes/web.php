<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use LifeWheel\Plugins\Goals\Events\GoalCreated;
use LifeWheel\Plugins\Goals\Events\GoalMilestoneCompleted;
use LifeWheel\Plugins\Goals\GoalAreas;
use LifeWheel\Plugins\Goals\GoalProgress;

require_once dirname(__DIR__).'/src/GoalAreas.php';
require_once dirname(__DIR__).'/src/GoalProgress.php';
require_once dirname(__DIR__).'/src/Events/GoalCreated.php';
require_once dirname(__DIR__).'/src/Events/GoalMilestoneCompleted.php';

Route::middleware(['auth', 'verified', 'twofactor', 'feature:goals.use'])
    ->prefix('app/goals')
    ->name('plugins.goals.')
    ->group(function (): void {
        Route::get('/', function (Request $request) {
            $status = $request->query('status', 'active');
            abort_unless(in_array($status, ['active', 'paused', 'completed', 'archived'], true), 404);

            $goals = DB::table('goals')
                ->where('user_id', $request->user()->id)
                ->where('status', $status)
                ->orderByRaw('due_date IS NULL, due_date ASC')
                ->latest()
                ->paginate(12)
                ->withQueryString();

            return View::file(dirname(__DIR__).'/resources/views/index.blade.php', [
                'goals' => $goals,
                'areas' => GoalAreas::all(),
                'status' => $status,
            ]);
        })->name('index');

        Route::post('/goals', function (Request $request) {
            $attributes = goalValidatedAttributes($request);

            $goalId = DB::table('goals')->insertGetId([
                'user_id' => $request->user()->id,
                'name' => $attributes['name'],
                'why' => $attributes['why'] ?? null,
                'areas' => json_encode($attributes['areas'] ?? []),
                'status' => $attributes['status'],
                'success_criterion' => $attributes['success_criterion'] ?? null,
                'measure' => $attributes['measure'] ?? null,
                'baseline' => $attributes['baseline'] ?? null,
                'current' => $attributes['current'] ?? null,
                'target' => $attributes['target'] ?? null,
                'unit' => $attributes['unit'] ?? null,
                'due_date' => $attributes['due_date'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            event(new GoalCreated($request->user(), $goalId));

            return redirect()->route('plugins.goals.show', $goalId)->with('status', 'goal-created');
        })->name('goals.store');

        Route::get('/goals/{goalId}', function (Request $request, int $goalId) {
            return View::file(dirname(__DIR__).'/resources/views/show.blade.php', goalViewData($request->user()->id, $goalId));
        })->name('show');

        Route::put('/goals/{goalId}', function (Request $request, int $goalId) {
            goalForUser($request->user()->id, $goalId);
            $attributes = goalValidatedAttributes($request);

            DB::table('goals')
                ->where('id', $goalId)
                ->where('user_id', $request->user()->id)
                ->update([
                    'name' => $attributes['name'],
                    'why' => $attributes['why'] ?? null,
                    'areas' => json_encode($attributes['areas'] ?? []),
                    'status' => $attributes['status'],
                    'success_criterion' => $attributes['success_criterion'] ?? null,
                    'measure' => $attributes['measure'] ?? null,
                    'baseline' => $attributes['baseline'] ?? null,
                    'current' => $attributes['current'] ?? null,
                    'target' => $attributes['target'] ?? null,
                    'unit' => $attributes['unit'] ?? null,
                    'due_date' => $attributes['due_date'] ?? null,
                    'updated_at' => now(),
                ]);

            return redirect()->route('plugins.goals.show', $goalId)->with('status', 'goal-updated');
        })->name('goals.update');

        Route::post('/goals/{goalId}/milestones', function (Request $request, int $goalId) {
            goalForUser($request->user()->id, $goalId);
            $attributes = $request->validate([
                'name' => ['required', 'string', 'max:180'],
                'notes' => ['nullable', 'string', 'max:3000'],
                'due_date' => ['nullable', 'date'],
            ]);

            DB::table('goal_milestones')->insert([
                'goal_id' => $goalId,
                'user_id' => $request->user()->id,
                'name' => $attributes['name'],
                'notes' => $attributes['notes'] ?? null,
                'due_date' => $attributes['due_date'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return back()->with('status', 'milestone-created');
        })->name('milestones.store');

        Route::put('/goals/{goalId}/milestones/{milestoneId}/complete', function (Request $request, int $goalId, int $milestoneId) {
            goalForUser($request->user()->id, $goalId);
            $updated = DB::table('goal_milestones')
                ->where('id', $milestoneId)
                ->where('goal_id', $goalId)
                ->where('user_id', $request->user()->id)
                ->whereNull('completed_at')
                ->update(['completed_at' => now(), 'updated_at' => now()]);

            abort_unless($updated === 1, 404);
            event(new GoalMilestoneCompleted($request->user(), $goalId, $milestoneId));

            return back()->with('status', 'milestone-completed');
        })->name('milestones.complete');

        Route::middleware('feature:goals.progress')->post('/goals/{goalId}/progress', function (Request $request, int $goalId) {
            goalForUser($request->user()->id, $goalId);
            $attributes = $request->validate([
                'value' => ['required', 'numeric'],
                'notes' => ['nullable', 'string', 'max:3000'],
                'recorded_on' => ['required', 'date'],
            ]);

            DB::transaction(function () use ($request, $goalId, $attributes): void {
                DB::table('goal_progress_records')->insert([
                    'goal_id' => $goalId,
                    'user_id' => $request->user()->id,
                    'value' => $attributes['value'],
                    'notes' => $attributes['notes'] ?? null,
                    'recorded_on' => $attributes['recorded_on'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('goals')
                    ->where('id', $goalId)
                    ->where('user_id', $request->user()->id)
                    ->update(['current' => $attributes['value'], 'updated_at' => now()]);
            });

            return back()->with('status', 'progress-recorded');
        })->name('progress.store');
    });

if (! function_exists('goalValidatedAttributes')) {
    function goalValidatedAttributes(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'why' => ['nullable', 'string', 'max:5000'],
            'areas' => ['array'],
            'areas.*' => ['string', 'in:'.implode(',', array_keys(GoalAreas::all()))],
            'status' => ['required', 'in:active,paused,completed,archived'],
            'success_criterion' => ['nullable', 'string', 'max:1000'],
            'measure' => ['nullable', 'string', 'max:120'],
            'baseline' => ['nullable', 'numeric'],
            'current' => ['nullable', 'numeric'],
            'target' => ['nullable', 'numeric'],
            'unit' => ['nullable', 'string', 'max:40'],
            'due_date' => ['nullable', 'date'],
        ]);
    }
}

if (! function_exists('goalForUser')) {
    function goalForUser(int $userId, int $goalId): object
    {
        $goal = DB::table('goals')
            ->where('id', $goalId)
            ->where('user_id', $userId)
            ->first();

        abort_unless($goal, 404);
        $goal->areas = json_decode((string) $goal->areas, true) ?: [];
        $goal->progress_percentage = GoalProgress::percentage((float) $goal->baseline, (float) $goal->current, (float) $goal->target);

        return $goal;
    }
}

if (! function_exists('goalViewData')) {
    function goalViewData(int $userId, int $goalId): array
    {
        return [
            'goal' => goalForUser($userId, $goalId),
            'areas' => GoalAreas::all(),
            'milestones' => DB::table('goal_milestones')->where('goal_id', $goalId)->where('user_id', $userId)->orderByRaw('completed_at IS NOT NULL')->orderBy('due_date')->get(),
            'progressRecords' => DB::table('goal_progress_records')->where('goal_id', $goalId)->where('user_id', $userId)->orderByDesc('recorded_on')->limit(12)->get(),
        ];
    }
}
