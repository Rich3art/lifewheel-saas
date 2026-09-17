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
            $latestReport = $latest ? lifeWheelCoachingReport((int) $latest->id, $request->user()->id) : null;

            return View::file(dirname(__DIR__).'/resources/views/index.blade.php', [
                'areas' => LifeWheelAreas::all(),
                'latest' => $latest,
                'previous' => $previous,
                'scores' => $scores,
                'previousScores' => $previousScores,
                'history' => $history,
                'latestReport' => $latestReport,
            ]);
        })->name('index');

        Route::post('/assessments', function (Request $request) {
            $rules = [
                'reflection' => ['nullable', 'string', 'max:5000'],
                'scores' => ['required', 'array'],
                'notes' => ['nullable', 'array'],
            ];

            foreach (LifeWheelAreas::keys() as $key) {
                $rules["scores.{$key}"] = ['required', 'integer', 'min:1', 'max:10'];
                $rules["notes.{$key}"] = ['nullable', 'string', 'max:2000'];
            }

            $attributes = $request->validate($rules);
            $areasByKey = collect(LifeWheelAreas::all())->keyBy('key');
            $overall = LifeWheelScoring::overall($attributes['scores']);
            $previous = latestLifeWheelAssessment($request->user()->id);
            $previousScores = $previous ? lifeWheelScores((int) $previous->id) : collect();

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
                        'note' => $attributes['notes'][$key] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                return $assessmentId;
            });

            $scores = lifeWheelScores($assessmentId);
            $report = buildLifeWheelCoachingReport(
                areas: LifeWheelAreas::all(),
                assessmentId: $assessmentId,
                currentOverall: $overall,
                previousOverall: $previous ? (float) $previous->overall_score : null,
                scores: $scores,
                previousScores: $previousScores,
                reflection: $attributes['reflection'] ?? null,
            );

            DB::table('lifewheel_coaching_reports')->updateOrInsert(
                ['assessment_id' => $assessmentId],
                [
                    'user_id' => $request->user()->id,
                    'provider_key' => 'lifeos',
                    'model' => 'category-coach-v1',
                    'content' => json_encode($report),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );

            event(new LifeWheelAssessmentCompleted($request->user(), $assessmentId, $overall));

            return redirect()
                ->route('plugins.lifewheel.history.show', $assessmentId)
                ->with('status', 'lifewheel-assessment-created');
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
                'report' => lifeWheelCoachingReport($assessmentId, $request->user()->id),
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

if (! function_exists('lifeWheelCoachingReport')) {
    function lifeWheelCoachingReport(int $assessmentId, int $userId): ?array
    {
        $report = DB::table('lifewheel_coaching_reports')
            ->where('assessment_id', $assessmentId)
            ->where('user_id', $userId)
            ->first();

        return $report ? (json_decode((string) $report->content, true) ?: null) : null;
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

if (! function_exists('buildLifeWheelCoachingReport')) {
    function buildLifeWheelCoachingReport(array $areas, int $assessmentId, float $currentOverall, ?float $previousOverall, \Illuminate\Support\Collection $scores, \Illuminate\Support\Collection $previousScores, ?string $reflection): array
    {
        $overallDelta = $previousOverall === null ? null : round($currentOverall - $previousOverall, 1);
        $missingNotes = [];
        $categoryFeedback = [];

        foreach ($areas as $area) {
            $score = (int) ($scores[$area['key']]->score ?? 0);
            $note = trim((string) ($scores[$area['key']]->note ?? ''));
            $previous = $previousScores[$area['key']] ?? null;
            $previousScore = $previous ? (int) $previous->score : null;
            $previousNote = $previous ? trim((string) ($previous->note ?? '')) : '';
            $delta = $previousScore === null ? null : $score - $previousScore;

            if ($note === '') {
                $missingNotes[] = $area['name'];
            }

            $categoryFeedback[] = [
                'area_key' => $area['key'],
                'area_name' => $area['name'],
                'group' => $area['group'],
                'score' => $score,
                'previous_score' => $previousScore,
                'change' => $delta,
                'note' => $note,
                'previous_note' => $previousNote,
                'feedback' => lifeWheelCategoryFeedback($area['name'], $score, $previousScore, $delta, $note, $previousNote),
                'next_step' => lifeWheelCategoryNextStep($area['name'], $score, $delta, $note),
            ];
        }

        return [
            'assessment_id' => $assessmentId,
            'overall_score' => round($currentOverall, 1),
            'previous_overall_score' => $previousOverall !== null ? round($previousOverall, 1) : null,
            'overall_change' => $overallDelta,
            'summary' => lifeWheelOverallFeedback($currentOverall, $previousOverall, $overallDelta, $reflection, $missingNotes),
            'missing_notes' => $missingNotes,
            'category_feedback' => $categoryFeedback,
            'created_at' => now()->toIso8601String(),
        ];
    }
}

if (! function_exists('lifeWheelOverallFeedback')) {
    function lifeWheelOverallFeedback(float $currentOverall, ?float $previousOverall, ?float $overallDelta, ?string $reflection, array $missingNotes): string
    {
        if ($previousOverall === null) {
            $message = 'This is your baseline Life Wheel. The power is not in judging the number; it is in giving your future self something honest to compare against.';
        } elseif ($overallDelta > 0) {
            $message = 'You moved from '.number_format($previousOverall, 1).' to '.number_format($currentOverall, 1).'. That is real forward motion. Keep noticing the specific choices that created the lift.';
        } elseif ($overallDelta < 0) {
            $message = 'You moved from '.number_format($previousOverall, 1).' to '.number_format($currentOverall, 1).'. That dip is information, not failure. Use it to protect energy, reset priorities, and choose one practical next step.';
        } else {
            $message = 'Your overall score stayed steady at '.number_format($currentOverall, 1).'. Stability can be useful; now look for one area where a small deliberate action can create momentum.';
        }

        if (trim((string) $reflection) !== '') {
            $message .= ' Your reflection gives the report more context, so keep writing even if it is short.';
        }

        if ($missingNotes !== []) {
            $message .= ' Next time, try adding a sentence for each category. Missing category notes make the coaching less personal for: '.implode(', ', $missingNotes).'.';
        }

        return $message;
    }
}

if (! function_exists('lifeWheelCategoryFeedback')) {
    function lifeWheelCategoryFeedback(string $areaName, int $score, ?int $previousScore, ?int $delta, string $note, string $previousNote): string
    {
        if ($previousScore === null) {
            $message = $areaName.' starts at '.$score.'/10. This is your starting line, not your identity. The next wheel will show what changed.';
        } elseif ($delta > 0) {
            $message = $areaName.' improved from '.$previousScore.' to '.$score.'. Well done. That kind of lift usually comes from repeated choices, even small ones.';
        } elseif ($delta < 0) {
            $message = $areaName.' moved from '.$previousScore.' to '.$score.'. Be kind with yourself here. A lower score is a signal to support this area more intentionally, not a reason to feel defeated.';
        } else {
            $message = $areaName.' stayed at '.$score.'. Consistency gives you a stable base; now choose one small action that could move it by one point.';
        }

        if ($note !== '' && $previousNote !== '') {
            $message .= ' Your current note adds useful context compared with last time, which makes the pattern more personal and easier to act on.';
        } elseif ($note !== '') {
            $message .= ' Good job adding context. This note will make the next comparison much more useful.';
        } else {
            $message .= ' Add a short note next time so your future report can understand what was happening behind the number.';
        }

        return $message;
    }
}

if (! function_exists('lifeWheelCategoryNextStep')) {
    function lifeWheelCategoryNextStep(string $areaName, int $score, ?int $delta, string $note): string
    {
        if ($note === '') {
            return 'Next time, write one sentence about what influenced your '.$areaName.' score.';
        }

        if ($score <= 4) {
            return 'Pick one gentle action you can repeat twice this week to support '.$areaName.'.';
        }

        if ($delta !== null && $delta < 0) {
            return 'Choose one stabilizing action this week and remove one pressure point where possible.';
        }

        if ($score >= 8) {
            return 'Protect what is working and write down the habit or condition that helped this score stay strong.';
        }

        return 'Choose one measurable next step that could raise '.$areaName.' by one point before your next Life Wheel.';
    }
}
