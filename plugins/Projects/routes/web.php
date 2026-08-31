<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use LifeWheel\Plugins\Projects\Events\ProjectCreated;
use LifeWheel\Plugins\Projects\Events\ProjectTaskCompleted;
use LifeWheel\Plugins\Projects\ProjectAreas;
use LifeWheel\Plugins\Projects\ProjectStats;

require_once dirname(__DIR__).'/src/ProjectAreas.php';
require_once dirname(__DIR__).'/src/ProjectStats.php';
require_once dirname(__DIR__).'/src/Events/ProjectCreated.php';
require_once dirname(__DIR__).'/src/Events/ProjectTaskCompleted.php';

Route::middleware(['auth', 'verified', 'twofactor', 'feature:projects.use'])
    ->prefix('app/projects')
    ->name('plugins.projects.')
    ->group(function (): void {
        Route::get('/', function (Request $request) {
            $status = $request->query('status', 'active');
            abort_unless(in_array($status, ['active', 'paused', 'completed', 'archived'], true), 404);

            $projects = DB::table('projects')
                ->where('user_id', $request->user()->id)
                ->where('status', $status)
                ->orderByRaw('due_date IS NULL, due_date ASC')
                ->latest()
                ->paginate(12)
                ->withQueryString();

            $projectIds = collect($projects->items())->pluck('id')->all();
            $taskCounts = DB::table('project_tasks')
                ->selectRaw('project_id, count(*) as total, sum(case when completed_at is not null then 1 else 0 end) as completed')
                ->whereIn('project_id', $projectIds ?: [0])
                ->where('user_id', $request->user()->id)
                ->groupBy('project_id')
                ->get()
                ->keyBy('project_id');

            return View::file(dirname(__DIR__).'/resources/views/index.blade.php', [
                'projects' => $projects,
                'taskCounts' => $taskCounts,
                'areas' => ProjectAreas::all(),
                'status' => $status,
            ]);
        })->name('index');

        Route::post('/projects', function (Request $request) {
            $attributes = projectValidatedAttributes($request);

            $projectId = DB::table('projects')->insertGetId([
                'user_id' => $request->user()->id,
                'name' => $attributes['name'],
                'description' => $attributes['description'] ?? null,
                'areas' => json_encode($attributes['areas'] ?? []),
                'status' => $attributes['status'],
                'priority' => $attributes['priority'],
                'start_date' => $attributes['start_date'] ?? null,
                'due_date' => $attributes['due_date'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            event(new ProjectCreated($request->user(), $projectId));

            return redirect()->route('plugins.projects.show', $projectId)->with('status', 'project-created');
        })->name('projects.store');

        Route::get('/projects/{projectId}', function (Request $request, int $projectId) {
            return View::file(dirname(__DIR__).'/resources/views/show.blade.php', projectViewData($request->user()->id, $projectId));
        })->name('show');

        Route::put('/projects/{projectId}', function (Request $request, int $projectId) {
            projectForUser($request->user()->id, $projectId);
            $attributes = projectValidatedAttributes($request);

            DB::table('projects')
                ->where('id', $projectId)
                ->where('user_id', $request->user()->id)
                ->update([
                    'name' => $attributes['name'],
                    'description' => $attributes['description'] ?? null,
                    'areas' => json_encode($attributes['areas'] ?? []),
                    'status' => $attributes['status'],
                    'priority' => $attributes['priority'],
                    'start_date' => $attributes['start_date'] ?? null,
                    'due_date' => $attributes['due_date'] ?? null,
                    'updated_at' => now(),
                ]);

            return redirect()->route('plugins.projects.show', $projectId)->with('status', 'project-updated');
        })->name('projects.update');

        Route::middleware('feature:projects.tasks')->post('/projects/{projectId}/tasks', function (Request $request, int $projectId) {
            projectForUser($request->user()->id, $projectId);
            $attributes = $request->validate([
                'title' => ['required', 'string', 'max:180'],
                'notes' => ['nullable', 'string', 'max:3000'],
                'due_date' => ['nullable', 'date'],
            ]);

            DB::table('project_tasks')->insert([
                'project_id' => $projectId,
                'user_id' => $request->user()->id,
                'title' => $attributes['title'],
                'notes' => $attributes['notes'] ?? null,
                'due_date' => $attributes['due_date'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return back()->with('status', 'project-task-created');
        })->name('tasks.store');

        Route::middleware('feature:projects.tasks')->put('/projects/{projectId}/tasks/{taskId}/complete', function (Request $request, int $projectId, int $taskId) {
            projectForUser($request->user()->id, $projectId);
            $updated = DB::table('project_tasks')
                ->where('id', $taskId)
                ->where('project_id', $projectId)
                ->where('user_id', $request->user()->id)
                ->whereNull('completed_at')
                ->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'updated_at' => now(),
                ]);

            abort_unless($updated === 1, 404);
            event(new ProjectTaskCompleted($request->user(), $projectId, $taskId));

            return back()->with('status', 'project-task-completed');
        })->name('tasks.complete');
    });

if (! function_exists('projectValidatedAttributes')) {
    function projectValidatedAttributes(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:5000'],
            'areas' => ['array'],
            'areas.*' => ['string', 'in:'.implode(',', array_keys(ProjectAreas::all()))],
            'status' => ['required', 'in:active,paused,completed,archived'],
            'priority' => ['required', 'in:low,medium,high,critical'],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
        ]);
    }
}

if (! function_exists('projectForUser')) {
    function projectForUser(int $userId, int $projectId): object
    {
        $project = DB::table('projects')->where('id', $projectId)->where('user_id', $userId)->first();
        abort_unless($project, 404);
        $project->areas = json_decode((string) $project->areas, true) ?: [];

        return $project;
    }
}

if (! function_exists('projectViewData')) {
    function projectViewData(int $userId, int $projectId): array
    {
        $tasks = DB::table('project_tasks')->where('project_id', $projectId)->where('user_id', $userId)->orderByRaw('completed_at IS NOT NULL')->orderBy('due_date')->get();
        $completed = $tasks->filter(fn (object $task): bool => $task->completed_at !== null)->count();

        return [
            'project' => projectForUser($userId, $projectId),
            'areas' => ProjectAreas::all(),
            'tasks' => $tasks,
            'completion' => ProjectStats::completion($completed, $tasks->count()),
        ];
    }
}
