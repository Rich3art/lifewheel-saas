<?php

namespace LifeWheel\Plugins\AiReviews;

use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class ReviewContextBuilder
{
    public function build(User $user, string $periodType, CarbonInterface $start, CarbonInterface $end): array
    {
        return [
            'period' => [
                'type' => $periodType,
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
            ],
            'lifewheel' => $this->lifewheel($user, $start, $end),
            'journal' => $this->datedRecords($user, 'journal_entries', 'entry_date', ['title', 'body', 'mood', 'energy'], $start, $end),
            'goals' => $this->goals($user, $start, $end),
            'habits' => $this->habits($user, $start, $end),
            'projects' => $this->projects($user, $start, $end),
            'lessons' => $this->datedRecords($user, 'lessons', 'learned_on', ['title', 'body', 'source_type'], $start, $end),
        ];
    }

    private function lifewheel(User $user, CarbonInterface $start, CarbonInterface $end): array
    {
        if (! Schema::hasTable('lifewheel_assessments') || ! Schema::hasTable('lifewheel_scores')) {
            return ['available' => false, 'assessments' => []];
        }

        $assessments = DB::table('lifewheel_assessments')
            ->where('user_id', $user->id)
            ->whereBetween('created_at', [$start, $end])
            ->orderByDesc('created_at')
            ->limit(6)
            ->get();

        return [
            'available' => true,
            'assessments' => $assessments->map(function (object $assessment) use ($user): array {
                return [
                    'overall_score' => (float) $assessment->overall_score,
                    'created_at' => $assessment->created_at,
                    'reflection_excerpt' => $this->excerpt($assessment->reflection ?? null),
                    'scores' => DB::table('lifewheel_scores')
                        ->where('assessment_id', $assessment->id)
                        ->where('user_id', $user->id)
                        ->get()
                        ->map(fn (object $score): array => [
                            'area' => $score->area_name,
                            'score' => (int) $score->score,
                        ])
                        ->all(),
                ];
            })->all(),
        ];
    }

    private function goals(User $user, CarbonInterface $start, CarbonInterface $end): array
    {
        if (! Schema::hasTable('goals')) {
            return ['available' => false, 'items' => []];
        }

        return [
            'available' => true,
            'items' => DB::table('goals')
                ->where('user_id', $user->id)
                ->where(function ($query) use ($start, $end): void {
                    $query->whereBetween('created_at', [$start, $end])
                        ->orWhereBetween('updated_at', [$start, $end]);
                })
                ->orderByDesc('updated_at')
                ->limit(10)
                ->get()
                ->map(fn (object $goal): array => [
                    'name' => $goal->name,
                    'status' => $goal->status,
                    'measure' => $goal->measure,
                    'baseline' => $goal->baseline,
                    'current' => $goal->current,
                    'target' => $goal->target,
                    'unit' => $goal->unit,
                    'due_date' => $goal->due_date,
                ])
                ->all(),
        ];
    }

    private function habits(User $user, CarbonInterface $start, CarbonInterface $end): array
    {
        if (! Schema::hasTable('habit_logs')) {
            return ['available' => false, 'items' => []];
        }

        $logs = DB::table('habit_logs')
            ->join('habits', 'habits.id', '=', 'habit_logs.habit_id')
            ->where('habit_logs.user_id', $user->id)
            ->whereBetween('habit_logs.logged_on', [$start->toDateString(), $end->toDateString()])
            ->groupBy('habits.id', 'habits.name')
            ->selectRaw('habits.id, habits.name, COUNT(*) as logged_count, SUM(CASE WHEN habit_logs.completed = 1 THEN 1 ELSE 0 END) as completed_count')
            ->limit(20)
            ->get();

        return [
            'available' => true,
            'items' => $logs->map(fn (object $habit): array => [
                'name' => $habit->name,
                'logged_count' => (int) $habit->logged_count,
                'completed_count' => (int) $habit->completed_count,
            ])->all(),
        ];
    }

    private function projects(User $user, CarbonInterface $start, CarbonInterface $end): array
    {
        if (! Schema::hasTable('projects')) {
            return ['available' => false, 'items' => []];
        }

        return [
            'available' => true,
            'items' => DB::table('projects')
                ->where('user_id', $user->id)
                ->where(function ($query) use ($start, $end): void {
                    $query->whereBetween('created_at', [$start, $end])
                        ->orWhereBetween('updated_at', [$start, $end]);
                })
                ->orderByDesc('updated_at')
                ->limit(10)
                ->get()
                ->map(fn (object $project): array => [
                    'name' => $project->name,
                    'status' => $project->status,
                    'priority' => $project->priority,
                    'due_date' => $project->due_date,
                    'description_excerpt' => $this->excerpt($project->description ?? null),
                ])
                ->all(),
        ];
    }

    private function datedRecords(User $user, string $table, string $dateColumn, array $columns, CarbonInterface $start, CarbonInterface $end): array
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'user_id') || ! Schema::hasColumn($table, $dateColumn)) {
            return ['available' => false, 'items' => []];
        }

        $existingColumns = collect($columns)
            ->filter(fn (string $column): bool => Schema::hasColumn($table, $column))
            ->values()
            ->all();

        return [
            'available' => true,
            'items' => DB::table($table)
                ->where('user_id', $user->id)
                ->whereBetween($dateColumn, [$start->toDateString(), $end->toDateString()])
                ->orderByDesc($dateColumn)
                ->limit(10)
                ->get()
                ->map(fn (object $record): array => $this->summarize($record, $existingColumns, $dateColumn))
                ->all(),
        ];
    }

    private function summarize(object $record, array $columns, string $dateColumn): array
    {
        $summary = ['date' => $record->{$dateColumn} ?? null];

        foreach ($columns as $column) {
            $summary[$column] = $this->excerpt($record->{$column} ?? null);
        }

        return $summary;
    }

    private function excerpt(mixed $value, int $limit = 500): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        return mb_substr(trim($value), 0, $limit);
    }
}
