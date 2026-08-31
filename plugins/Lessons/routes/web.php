<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Validation\Rule;
use LifeWheel\Plugins\Lessons\Events\LessonCreated;
use LifeWheel\Plugins\Lessons\LessonAreas;

require_once dirname(__DIR__).'/src/LessonAreas.php';
require_once dirname(__DIR__).'/src/Events/LessonCreated.php';

Route::middleware(['auth', 'verified', 'twofactor', 'feature:lessons.use'])
    ->prefix('app/lessons')
    ->name('plugins.lessons.')
    ->group(function (): void {
        Route::get('/', function (Request $request) {
            $search = trim((string) $request->query('search'));
            $canSearch = app(\App\Services\EntitlementService::class)->userHasFeature($request->user(), 'lessons.search');

            abort_if($search !== '' && ! $canSearch, 403);

            $lessons = DB::table('lessons')
                ->where('user_id', $request->user()->id)
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where(function ($query) use ($search): void {
                        $query->where('title', 'like', "%{$search}%")
                            ->orWhere('body', 'like', "%{$search}%");
                    });
                })
                ->orderByDesc('learned_on')
                ->orderByDesc('created_at')
                ->paginate(12)
                ->withQueryString();

            return View::file(dirname(__DIR__).'/resources/views/index.blade.php', [
                'lessons' => $lessons,
                'areas' => LessonAreas::all(),
                'search' => $search,
                'canSearch' => $canSearch,
            ]);
        })->name('index');

        Route::post('/lessons', function (Request $request) {
            $attributes = lessonValidatedAttributes($request);
            $idempotencyKey = $attributes['idempotency_key'] ?? hash('sha256', $request->user()->id.'|manual|'.$attributes['title'].'|'.$attributes['learned_on']);

            $lessonId = DB::table('lessons')->insertGetId([
                'user_id' => $request->user()->id,
                'title' => $attributes['title'],
                'body' => $attributes['body'],
                'areas' => json_encode($attributes['areas'] ?? []),
                'source_type' => $attributes['source_type'] ?? 'manual',
                'source_id' => $attributes['source_id'] ?? null,
                'idempotency_key' => $idempotencyKey,
                'learned_on' => $attributes['learned_on'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            event(new LessonCreated($request->user(), $lessonId));

            return redirect()->route('plugins.lessons.show', $lessonId)->with('status', 'lesson-created');
        })->name('lessons.store');

        Route::get('/lessons/{lessonId}', function (Request $request, int $lessonId) {
            return View::file(dirname(__DIR__).'/resources/views/show.blade.php', [
                'lesson' => lessonForUser($request->user()->id, $lessonId),
                'areas' => LessonAreas::all(),
            ]);
        })->name('show');

        Route::put('/lessons/{lessonId}', function (Request $request, int $lessonId) {
            lessonForUser($request->user()->id, $lessonId);
            $attributes = lessonValidatedAttributes($request, updating: true);

            DB::table('lessons')
                ->where('id', $lessonId)
                ->where('user_id', $request->user()->id)
                ->update([
                    'title' => $attributes['title'],
                    'body' => $attributes['body'],
                    'areas' => json_encode($attributes['areas'] ?? []),
                    'learned_on' => $attributes['learned_on'],
                    'updated_at' => now(),
                ]);

            return redirect()->route('plugins.lessons.show', $lessonId)->with('status', 'lesson-updated');
        })->name('lessons.update');

        Route::delete('/lessons/{lessonId}', function (Request $request, int $lessonId) {
            lessonForUser($request->user()->id, $lessonId);

            DB::table('lessons')
                ->where('id', $lessonId)
                ->where('user_id', $request->user()->id)
                ->delete();

            return redirect()->route('plugins.lessons.index')->with('status', 'lesson-deleted');
        })->name('lessons.destroy');
    });

if (! function_exists('lessonValidatedAttributes')) {
    function lessonValidatedAttributes(Request $request, bool $updating = false): array
    {
        $rules = [
            'title' => ['required', 'string', 'max:180'],
            'body' => ['required', 'string', 'max:20000'],
            'areas' => ['array'],
            'areas.*' => ['string', 'in:'.implode(',', array_keys(LessonAreas::all()))],
            'learned_on' => ['required', 'date'],
        ];

        if (! $updating) {
            $rules['source_type'] = ['nullable', 'string', 'max:80'];
            $rules['source_id'] = ['nullable', 'string', 'max:120'];
            $rules['idempotency_key'] = [
                'nullable',
                'string',
                'max:120',
                Rule::unique('lessons', 'idempotency_key')->where('user_id', $request->user()->id),
            ];
        }

        return $request->validate($rules);
    }
}

if (! function_exists('lessonForUser')) {
    function lessonForUser(int $userId, int $lessonId): object
    {
        $lesson = DB::table('lessons')->where('id', $lessonId)->where('user_id', $userId)->first();
        abort_unless($lesson, 404);
        $lesson->areas = json_decode((string) $lesson->areas, true) ?: [];

        return $lesson;
    }
}
