<?php

namespace LifeWheel\Plugins\AiLifeAnalysis;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class LifeContextBuilder
{
    public function build(User $user): array
    {
        $context = [
            'user' => [
                'timezone' => $user->timezone,
            ],
            'lifewheel' => [
                'assessments' => [],
                'latest_scores' => [],
                'score_changes' => [],
            ],
        ];

        if (! Schema::hasTable('lifewheel_assessments') || ! Schema::hasTable('lifewheel_scores')) {
            return $context;
        }

        $assessments = DB::table('lifewheel_assessments')
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(6)
            ->get();

        $context['lifewheel']['assessments'] = $assessments->map(fn (object $assessment): array => [
            'id' => $assessment->id,
            'overall_score' => (float) $assessment->overall_score,
            'created_at' => $assessment->created_at,
            'reflection_excerpt' => $assessment->reflection ? mb_substr((string) $assessment->reflection, 0, 500) : null,
        ])->all();

        $latest = $assessments->first();
        $previous = $assessments->skip(1)->first();

        if (! $latest) {
            return $context;
        }

        $latestScores = DB::table('lifewheel_scores')
            ->where('assessment_id', $latest->id)
            ->where('user_id', $user->id)
            ->get()
            ->keyBy('area_key');

        $previousScores = $previous
            ? DB::table('lifewheel_scores')->where('assessment_id', $previous->id)->where('user_id', $user->id)->get()->keyBy('area_key')
            : collect();

        $context['lifewheel']['latest_scores'] = $latestScores->map(fn (object $score): array => [
            'area' => $score->area_name,
            'group' => $score->area_group,
            'score' => (int) $score->score,
        ])->values()->all();

        $context['lifewheel']['score_changes'] = $latestScores->map(function (object $score) use ($previousScores): array {
            $previous = $previousScores[$score->area_key] ?? null;

            return [
                'area' => $score->area_name,
                'current' => (int) $score->score,
                'previous' => $previous ? (int) $previous->score : null,
                'change' => $previous ? (int) $score->score - (int) $previous->score : null,
            ];
        })->values()->all();

        return $context;
    }
}
