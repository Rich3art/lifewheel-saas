<?php

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use LifeWheel\Plugins\Habits\Events\HabitCompleted;
use LifeWheel\Plugins\Habits\HabitAreas;
use LifeWheel\Plugins\Habits\HabitStats;

require_once dirname(__DIR__).'/src/HabitAreas.php';
require_once dirname(__DIR__).'/src/HabitStats.php';
require_once dirname(__DIR__).'/src/Events/HabitCompleted.php';

Route::middleware(['auth', 'verified', 'twofactor', 'feature:habits.use'])
    ->prefix('app/habits')
    ->name('plugins.habits.')
    ->group(function (): void {
        Route::get('/', function (Request $request) {
            $habits = DB::table('habits')
                ->where('user_id', $request->user()->id)
                ->where('status', 'active')
                ->orderBy('name')
                ->get()
                ->map(function (object $habit) use ($request): object {
                    $habit->areas = json_decode((string) $habit->areas, true) ?: [];
                    $habit->weekdays = json_decode((string) $habit->weekdays, true) ?: [];
                    $expected = habitExpectedCount($habit, now()->subDays(27), now());
                    $completed = DB::table('habit_logs')
                        ->where('habit_id', $habit->id)
                        ->where('user_id', $request->user()->id)
                        ->where('completed', true)
                        ->whereBetween('logged_on', [now()->subDays(27)->toDateString(), now()->toDateString()])
                        ->count();
                    $habit->adherence = HabitStats::adherence($completed, $expected);
                    $habit->logged_today = DB::table('habit_logs')
                        ->where('habit_id', $habit->id)
                        ->where('user_id', $request->user()->id)
                        ->where('logged_on', now()->toDateString())
                        ->exists();

                    return $habit;
                });

            return View::file(dirname(__DIR__).'/resources/views/index.blade.php', [
                'habits' => $habits,
                'areas' => HabitAreas::all(),
                'weekdays' => habitWeekdays(),
            ]);
        })->name('index');

        Route::post('/habits', function (Request $request) {
            $attributes = habitValidatedAttributes($request);

            $habitId = DB::table('habits')->insertGetId([
                'user_id' => $request->user()->id,
                'name' => $attributes['name'],
                'type' => $attributes['type'],
                'areas' => json_encode($attributes['areas'] ?? []),
                'weekdays' => json_encode($attributes['weekdays'] ?? array_keys(habitWeekdays())),
                'target_count' => $attributes['target_count'],
                'target_value' => $attributes['target_value'] ?? null,
                'unit' => $attributes['unit'] ?? null,
                'status' => $attributes['status'],
                'notes' => $attributes['notes'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return redirect()->route('plugins.habits.show', $habitId)->with('status', 'habit-created');
        })->name('habits.store');

        Route::get('/habits/{habitId}', function (Request $request, int $habitId) {
            return View::file(dirname(__DIR__).'/resources/views/show.blade.php', habitViewData($request->user()->id, $habitId));
        })->name('show');

        Route::put('/habits/{habitId}', function (Request $request, int $habitId) {
            habitForUser($request->user()->id, $habitId);
            $attributes = habitValidatedAttributes($request);

            DB::table('habits')
                ->where('id', $habitId)
                ->where('user_id', $request->user()->id)
                ->update([
                    'name' => $attributes['name'],
                    'type' => $attributes['type'],
                    'areas' => json_encode($attributes['areas'] ?? []),
                    'weekdays' => json_encode($attributes['weekdays'] ?? array_keys(habitWeekdays())),
                    'target_count' => $attributes['target_count'],
                    'target_value' => $attributes['target_value'] ?? null,
                    'unit' => $attributes['unit'] ?? null,
                    'status' => $attributes['status'],
                    'notes' => $attributes['notes'] ?? null,
                    'updated_at' => now(),
                ]);

            return redirect()->route('plugins.habits.show', $habitId)->with('status', 'habit-updated');
        })->name('habits.update');

        Route::post('/habits/{habitId}/logs', function (Request $request, int $habitId) {
            habitForUser($request->user()->id, $habitId);
            $attributes = $request->validate([
                'logged_on' => ['required', 'date'],
                'completed' => ['nullable', 'boolean'],
                'value' => ['nullable', 'numeric'],
                'mood' => ['nullable', 'string', 'max:40'],
                'notes' => ['nullable', 'string', 'max:3000'],
            ]);

            $logId = DB::table('habit_logs')->updateOrInsert(
                ['habit_id' => $habitId, 'logged_on' => $attributes['logged_on']],
                [
                    'user_id' => $request->user()->id,
                    'completed' => (bool) ($attributes['completed'] ?? false),
                    'value' => $attributes['value'] ?? null,
                    'mood' => $attributes['mood'] ?? null,
                    'notes' => $attributes['notes'] ?? null,
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );

            $log = DB::table('habit_logs')
                ->where('habit_id', $habitId)
                ->where('user_id', $request->user()->id)
                ->where('logged_on', $attributes['logged_on'])
                ->first();

            if (($attributes['completed'] ?? false) && $log) {
                event(new HabitCompleted($request->user(), $habitId, (int) $log->id));
            }

            return back()->with('status', 'habit-logged');
        })->name('logs.store');
    });

if (! function_exists('habitValidatedAttributes')) {
    function habitValidatedAttributes(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'type' => ['required', 'in:positive,quit,numeric'],
            'areas' => ['array'],
            'areas.*' => ['string', 'in:'.implode(',', array_keys(HabitAreas::all()))],
            'weekdays' => ['array'],
            'weekdays.*' => ['integer', 'min:0', 'max:6'],
            'target_count' => ['required', 'integer', 'min:1', 'max:99'],
            'target_value' => ['nullable', 'numeric'],
            'unit' => ['nullable', 'string', 'max:40'],
            'status' => ['required', 'in:active,paused,archived'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);
    }
}

if (! function_exists('habitForUser')) {
    function habitForUser(int $userId, int $habitId): object
    {
        $habit = DB::table('habits')->where('id', $habitId)->where('user_id', $userId)->first();
        abort_unless($habit, 404);
        $habit->areas = json_decode((string) $habit->areas, true) ?: [];
        $habit->weekdays = json_decode((string) $habit->weekdays, true) ?: [];

        return $habit;
    }
}

if (! function_exists('habitViewData')) {
    function habitViewData(int $userId, int $habitId): array
    {
        return [
            'habit' => habitForUser($userId, $habitId),
            'areas' => HabitAreas::all(),
            'weekdays' => habitWeekdays(),
            'logs' => DB::table('habit_logs')->where('habit_id', $habitId)->where('user_id', $userId)->orderByDesc('logged_on')->limit(30)->get(),
        ];
    }
}

if (! function_exists('habitExpectedCount')) {
    function habitExpectedCount(object $habit, Carbon $start, Carbon $end): int
    {
        $weekdays = $habit->weekdays ?: array_keys(habitWeekdays());
        $count = 0;

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            if (in_array($date->dayOfWeek, $weekdays, false)) {
                $count += (int) $habit->target_count;
            }
        }

        return $count;
    }
}

if (! function_exists('habitWeekdays')) {
    function habitWeekdays(): array
    {
        return [
            0 => 'Sun',
            1 => 'Mon',
            2 => 'Tue',
            3 => 'Wed',
            4 => 'Thu',
            5 => 'Fri',
            6 => 'Sat',
        ];
    }
}
