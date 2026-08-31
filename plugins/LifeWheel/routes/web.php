<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use LifeWheel\Plugins\LifeWheel\Events\LifeWheelAssessmentCompleted;
use LifeWheel\Plugins\LifeWheel\LifeWheelAreas;
use LifeWheel\Plugins\LifeWheel\LifeWheelScoring;

require_once dirname(__DIR__).'/src/LifeWheelAreas.php';
require_once dirname(__DIR__).'/src/LifeWheelScoring.php';
require_once dirname(__DIR__).'/src/Events/LifeWheelAssessmentCompleted.php';

Route::middleware(['auth', 'verified', 'twofactor', 'feature:lifewheel.use'])
    ->prefix('app/lifewheel')
    ->name('plugins.lifewheel.')
    ->group(function (): void {
        Route::get('/', function (Request $request) {
            $latest = latestLifeWheelAssessment($request->user()->id);
            $previous = $latest ? previousLifeWheelAssessment($request->user()->id, (int) $latest->id) : null;
            $scores = $latest ? lifeWheelScores((int) $latest->id) : collect();
            $previousScores = $previous ? lifeWheelScores((int) $previous->id) : collect();
            $history = lifeWheelHistory($request->user()->id, 8);

            return View::file(dirname(__DIR__).'/resources/views/index.blade.php', [
                'areas' => LifeWheelAreas::all(),
                'latest' => $latest,
                'previous' => $previous,
                'scores' => $scores,
                'previousScores' => $previousScores,
                'history' => $history,
            ]);
        })->name('index');

        Route::post('/assessments', function (Request $request) {
            $rules = [
                'reflection' => ['nullable', 'string', 'max:5000'],
                'scores' => ['required', 'array'],
            ];

            foreach (LifeWheelAreas::keys() as $key) {
                $rules["scores.{$key}"] = ['required', 'integer', 'min:1', 'max:10'];
            }

            $attributes = $request->validate($rules);
            $areasByKey = collect(LifeWheelAreas::all())->keyBy('key');
            $overall = LifeWheelScoring::overall($attributes['scores']);

            $assessmentId = DB::transaction(function () use ($request, $attributes, $areasByKey, $overall): int {
                $assessmentId = DB::table('lifewheel_assessments')->insertGetId([
                    'user_id' => $request->user()->id,
                    'overall_score' => $overall,
                    'reflection' => $attributes['reflection'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                foreach ($attributes['scores'] as $key => $score) {
                    $area = $areasByKey->get($key);
                    DB::table('lifewheel_scores')->insert([
                        'assessment_id' => $assessmentId,
                        'user_id' => $request->user()->id,
                        'area_key' => $key,
                        'area_name' => $area['name'],
                        'area_group' => $area['group'],
                        'score' => $score,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                return $assessmentId;
            });

            event(new LifeWheelAssessmentCompleted($request->user(), $assessmentId, $overall));

            return redirect()->route('plugins.lifewheel.index')->with('status', 'lifewheel-assessment-created');
        })->name('assessments.store');

        Route::middleware('feature:lifewheel.history')->get('/history/{assessmentId}', function (Request $request, int $assessmentId) {
            $assessment = DB::table('lifewheel_assessments')
                ->where('id', $assessmentId)
                ->where('user_id', $request->user()->id)
                ->first();

            abort_unless($assessment, 404);

            return View::file(dirname(__DIR__).'/resources/views/show.blade.php', [
                'assessment' => $assessment,
                'scores' => lifeWheelScores($assessmentId),
                'areas' => LifeWheelAreas::all(),
            ]);
        })->name('history.show');
    });

if (! function_exists('latestLifeWheelAssessment')) {
    function latestLifeWheelAssessment(int $userId): ?object
    {
        return DB::table('lifewheel_assessments')
            ->where('user_id', $userId)
            ->latest()
            ->first();
    }
}

if (! function_exists('previousLifeWheelAssessment')) {
    function previousLifeWheelAssessment(int $userId, int $latestAssessmentId): ?object
    {
        return DB::table('lifewheel_assessments')
            ->where('user_id', $userId)
            ->where('id', '!=', $latestAssessmentId)
            ->latest()
            ->first();
    }
}

if (! function_exists('lifeWheelScores')) {
    function lifeWheelScores(int $assessmentId): \Illuminate\Support\Collection
    {
        $areaOrder = array_flip(LifeWheelAreas::keys());

        return DB::table('lifewheel_scores')
            ->where('assessment_id', $assessmentId)
            ->get()
            ->sortBy(fn (object $score): int => $areaOrder[$score->area_key] ?? 999)
            ->keyBy('area_key');
    }
}

if (! function_exists('lifeWheelHistory')) {
    function lifeWheelHistory(int $userId, int $limit): \Illuminate\Support\Collection
    {
        return DB::table('lifewheel_assessments')
            ->where('user_id', $userId)
            ->latest()
            ->limit($limit)
            ->get();
    }
}
