<?php

use App\Models\AiPromptSetting;
use App\Services\AI\AiGateway;
use App\Services\AI\AiRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
                'notes' => ['required', 'array'],
            ];

            foreach (LifeWheelAreas::keys() as $key) {
                $rules["scores.{$key}"] = ['required', 'integer', 'min:1', 'max:10'];
                $rules["notes.{$key}"] = ['required', 'string', 'min:3', 'max:2000'];
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
            $fallbackReport = buildLifeWheelCoachingReport(
                areas: LifeWheelAreas::all(),
                assessmentId: $assessmentId,
                currentOverall: $overall,
                previousOverall: $previous ? (float) $previous->overall_score : null,
                scores: $scores,
                previousScores: $previousScores,
                reflection: $attributes['reflection'] ?? null,
            );
            $aiReport = generateLifeWheelAiCoachingReport(
                request: $request,
                assessmentId: $assessmentId,
                currentOverall: $overall,
                previousOverall: $previous ? (float) $previous->overall_score : null,
                scores: $scores,
                previousScores: $previousScores,
                reflection: $attributes['reflection'] ?? null,
                fallbackReport: $fallbackReport,
            );
            $report = $aiReport['report'];

            DB::table('lifewheel_coaching_reports')->updateOrInsert(
                ['assessment_id' => $assessmentId],
                [
                    'user_id' => $request->user()->id,
                    'provider_key' => $aiReport['provider_key'],
                    'model' => $aiReport['model'],
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
        $categoryFeedback = [];

        foreach ($areas as $area) {
            $score = (int) ($scores[$area['key']]->score ?? 0);
            $note = trim((string) ($scores[$area['key']]->note ?? ''));
            $previous = $previousScores[$area['key']] ?? null;
            $previousScore = $previous ? (int) $previous->score : null;
            $previousNote = $previous ? trim((string) ($previous->note ?? '')) : '';
            $delta = $previousScore === null ? null : $score - $previousScore;

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
            ];
        }

        return [
            'assessment_id' => $assessmentId,
            'overall_score' => round($currentOverall, 1),
            'previous_overall_score' => $previousOverall !== null ? round($previousOverall, 1) : null,
            'overall_change' => $overallDelta,
            'summary' => lifeWheelOverallFeedback($currentOverall, $previousOverall, $overallDelta, $reflection),
            'category_feedback' => $categoryFeedback,
            'created_at' => now()->toIso8601String(),
        ];
    }
}

if (! function_exists('generateLifeWheelAiCoachingReport')) {
    function generateLifeWheelAiCoachingReport(Request $request, int $assessmentId, float $currentOverall, ?float $previousOverall, \Illuminate\Support\Collection $scores, \Illuminate\Support\Collection $previousScores, ?string $reflection, array $fallbackReport): array
    {
        try {
            $response = app(AiGateway::class)->generate(new AiRequest(
                featureSlug: 'ai.coach',
                systemPrompt: lifeWheelAiSystemPrompt(),
                userPrompt: lifeWheelAiUserPrompt(
                    assessmentId: $assessmentId,
                    currentOverall: $currentOverall,
                    previousOverall: $previousOverall,
                    scores: $scores,
                    previousScores: $previousScores,
                    reflection: $reflection,
                ),
                responseSchema: lifeWheelAiResponseSchema(),
                user: $request->user(),
                metadata: [
                    'source' => 'lifewheel.feedback',
                    'assessment_id' => $assessmentId,
                ],
            ));

            $report = normalizeLifeWheelAiReport(
                assessmentId: $assessmentId,
                currentOverall: $currentOverall,
                previousOverall: $previousOverall,
                scores: $scores,
                previousScores: $previousScores,
                structured: $response->structured,
                fallbackReport: $fallbackReport,
            );

            return [
                'provider_key' => $response->providerKey,
                'model' => $response->model,
                'report' => $report,
            ];
        } catch (\Throwable $exception) {
            Log::warning('LifeWheel AI feedback fell back to local coaching text.', [
                'assessment_id' => $assessmentId,
                'user_id' => $request->user()->id,
                'error' => $exception->getMessage(),
            ]);

            return [
                'provider_key' => 'lifeos',
                'model' => 'category-coach-v1',
                'report' => $fallbackReport,
            ];
        }
    }
}

if (! function_exists('lifeWheelAiSystemPrompt')) {
    function lifeWheelAiSystemPrompt(): string
    {
        $prompt = AiPromptSetting::query()
            ->where('key', 'lifewheel.feedback.system_prompt')
            ->value('prompt');

        return trim((string) $prompt)."\n\nReturn concise JSON only. The JSON must contain one overall summary and one personalized feedback item for every category in the supplied order.";
    }
}

if (! function_exists('lifeWheelAiUserPrompt')) {
    function lifeWheelAiUserPrompt(int $assessmentId, float $currentOverall, ?float $previousOverall, \Illuminate\Support\Collection $scores, \Illuminate\Support\Collection $previousScores, ?string $reflection): string
    {
        $categories = [];

        foreach (LifeWheelAreas::all() as $area) {
            $score = $scores[$area['key']] ?? null;
            $previous = $previousScores[$area['key']] ?? null;

            $categories[] = [
                'area_key' => $area['key'],
                'area_name' => $area['name'],
                'group' => $area['group'],
                'current_score' => $score ? (int) $score->score : null,
                'current_note' => $score ? (string) ($score->note ?? '') : '',
                'previous_score' => $previous ? (int) $previous->score : null,
                'previous_note' => $previous ? (string) ($previous->note ?? '') : '',
            ];
        }

        return json_encode([
            'task' => 'Create LifeWheel AI Coach Feedback for this exact submission.',
            'assessment_id' => $assessmentId,
            'current_overall_score' => round($currentOverall, 1),
            'previous_overall_score' => $previousOverall !== null ? round($previousOverall, 1) : null,
            'overall_reflection' => (string) ($reflection ?? ''),
            'categories' => $categories,
            'output_rules' => [
                'summary' => 'One warm paragraph for the full wheel.',
                'category_feedback' => 'Exactly one item per category, using the same area_key values.',
                'tone' => 'Encouraging, personal, grounded, not repetitive.',
            ],
        ], JSON_PRETTY_PRINT);
    }
}

if (! function_exists('lifeWheelAiResponseSchema')) {
    function lifeWheelAiResponseSchema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['summary', 'category_feedback'],
            'properties' => [
                'summary' => [
                    'type' => 'string',
                    'minLength' => 20,
                ],
                'category_feedback' => [
                    'type' => 'array',
                    'minItems' => count(LifeWheelAreas::all()),
                    'maxItems' => count(LifeWheelAreas::all()),
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['area_key', 'area_name', 'feedback'],
                        'properties' => [
                            'area_key' => ['type' => 'string'],
                            'area_name' => ['type' => 'string'],
                            'feedback' => ['type' => 'string', 'minLength' => 20],
                        ],
                    ],
                ],
            ],
        ];
    }
}

if (! function_exists('normalizeLifeWheelAiReport')) {
    function normalizeLifeWheelAiReport(int $assessmentId, float $currentOverall, ?float $previousOverall, \Illuminate\Support\Collection $scores, \Illuminate\Support\Collection $previousScores, array $structured, array $fallbackReport): array
    {
        $feedbackByKey = collect($structured['category_feedback'] ?? [])
            ->filter(fn ($item): bool => is_array($item) && isset($item['area_key'], $item['feedback']))
            ->keyBy('area_key');

        if (! is_string($structured['summary'] ?? null) || $feedbackByKey->count() < count(LifeWheelAreas::all())) {
            return $fallbackReport;
        }

        $categories = [];

        foreach (LifeWheelAreas::all() as $area) {
            $score = $scores[$area['key']] ?? null;
            $previous = $previousScores[$area['key']] ?? null;
            $aiFeedback = $feedbackByKey->get($area['key']);

            $categories[] = [
                'area_key' => $area['key'],
                'area_name' => $area['name'],
                'group' => $area['group'],
                'score' => $score ? (int) $score->score : null,
                'previous_score' => $previous ? (int) $previous->score : null,
                'change' => ($score && $previous) ? (int) $score->score - (int) $previous->score : null,
                'note' => $score ? (string) ($score->note ?? '') : '',
                'previous_note' => $previous ? (string) ($previous->note ?? '') : '',
                'feedback' => trim((string) $aiFeedback['feedback']),
            ];
        }

        return [
            'assessment_id' => $assessmentId,
            'overall_score' => round($currentOverall, 1),
            'previous_overall_score' => $previousOverall !== null ? round($previousOverall, 1) : null,
            'overall_change' => $previousOverall === null ? null : round($currentOverall - $previousOverall, 1),
            'summary' => trim((string) $structured['summary']),
            'category_feedback' => $categories,
            'created_at' => now()->toIso8601String(),
        ];
    }
}

if (! function_exists('lifeWheelOverallFeedback')) {
    function lifeWheelOverallFeedback(float $currentOverall, ?float $previousOverall, ?float $overallDelta, ?string $reflection): string
    {
        if ($previousOverall === null) {
            $message = 'This is your baseline Life Wheel. The score is not a judgment; it is the first honest snapshot your future reports will compare against.';
        } elseif ($overallDelta > 0) {
            $message = 'Your overall Life Score moved from '.number_format($previousOverall, 1).' to '.number_format($currentOverall, 1).'. That is forward movement worth noticing, especially because your category notes now show what was happening behind the numbers.';
        } elseif ($overallDelta < 0) {
            $message = 'Your overall Life Score moved from '.number_format($previousOverall, 1).' to '.number_format($currentOverall, 1).'. Treat that as useful information, not failure; the notes you added are what make the next step clearer.';
        } else {
            $message = 'Your overall Life Score stayed at '.number_format($currentOverall, 1).'. Stability can still be progress when you understand what is keeping things steady.';
        }

        if (trim((string) $reflection) !== '') {
            $message .= ' Your overall reflection adds helpful context across the whole wheel.';
        }

        return $message;
    }
}

if (! function_exists('lifeWheelCategoryFeedback')) {
    function lifeWheelCategoryFeedback(string $areaName, int $score, ?int $previousScore, ?int $delta, string $note, string $previousNote): string
    {
        $context = lifeWheelNotePreview($note);
        $previousContext = lifeWheelNotePreview($previousNote);

        if ($previousScore === null) {
            $message = $areaName.' is starting at '.$score.'/10, with your own context being: '.$context.'. This gives the next Life Wheel something personal to compare against.';
        } elseif ($delta > 0) {
            $message = $areaName.' rose from '.$previousScore.' to '.$score.'. Compared with last time'.($previousContext ? ', when you wrote '.$previousContext : '').', your current note shows a different season: '.$context.'. That is encouraging movement, and it is worth protecting what helped.';
        } elseif ($delta < 0) {
            $message = $areaName.' moved from '.$previousScore.' to '.$score.'. Your note says '.$context.', so the lower score should be treated as a signal for care and adjustment, not as something to be ashamed of.';
        } else {
            $message = $areaName.' stayed at '.$score.', but the story still matters. Your note says '.$context.'. Use that detail to decide whether this area needs maintenance, patience, or a small push.';
        }

        return $message;
    }
}

if (! function_exists('lifeWheelNotePreview')) {
    function lifeWheelNotePreview(string $note): string
    {
        return trim(mb_substr(preg_replace('/\s+/', ' ', $note), 0, 220));
    }
}
