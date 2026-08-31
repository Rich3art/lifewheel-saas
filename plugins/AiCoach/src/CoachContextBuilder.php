<?php

namespace LifeWheel\Plugins\AiCoach;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

final class CoachContextBuilder
{
    public function build(User $user, string $question): array
    {
        $keywords = $this->keywords($question);

        return [
            'question' => $question,
            'retrieval_policy' => [
                'scope' => 'authenticated_user_only',
                'strategy' => 'recent_records_plus_keyword_matches',
                'keywords' => $keywords,
            ],
            'lifewheel' => $this->lifewheel($user),
            'journal' => $this->records($user, 'journal_entries', ['title', 'body'], $keywords, 5),
            'goals' => $this->records($user, 'goals', ['name', 'why', 'status', 'success_criterion'], $keywords, 5),
            'habits' => $this->records($user, 'habits', ['name', 'type', 'target_unit'], $keywords, 5),
            'projects' => $this->records($user, 'projects', ['name', 'description', 'status'], $keywords, 5),
            'lessons' => $this->records($user, 'lessons', ['title', 'body'], $keywords, 5),
            'recent_coach_messages' => $this->recentMessages($user),
        ];
    }

    private function keywords(string $question): array
    {
        return collect(preg_split('/[^a-z0-9]+/i', Str::lower($question)) ?: [])
            ->filter(fn (string $word): bool => strlen($word) >= 4)
            ->reject(fn (string $word): bool => in_array($word, ['what', 'when', 'where', 'which', 'have', 'this', 'that', 'with', 'from', 'about', 'your', 'life'], true))
            ->unique()
            ->take(8)
            ->values()
            ->all();
    }

    private function lifewheel(User $user): array
    {
        if (! Schema::hasTable('lifewheel_assessments') || ! Schema::hasTable('lifewheel_scores')) {
            return ['available' => false, 'recent_assessments' => [], 'latest_scores' => []];
        }

        $assessments = DB::table('lifewheel_assessments')
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(4)
            ->get();

        $latest = $assessments->first();

        return [
            'available' => true,
            'recent_assessments' => $assessments->map(fn (object $assessment): array => [
                'overall_score' => (float) $assessment->overall_score,
                'created_at' => $assessment->created_at,
                'reflection_excerpt' => $this->excerpt($assessment->reflection ?? null),
            ])->all(),
            'latest_scores' => $latest ? DB::table('lifewheel_scores')
                ->where('assessment_id', $latest->id)
                ->where('user_id', $user->id)
                ->orderBy('area_group')
                ->orderBy('area_name')
                ->get()
                ->map(fn (object $score): array => [
                    'area' => $score->area_name,
                    'group' => $score->area_group,
                    'score' => (int) $score->score,
                ])
                ->all() : [],
        ];
    }

    private function records(User $user, string $table, array $columns, array $keywords, int $limit): array
    {
        if (! Schema::hasTable($table)) {
            return ['available' => false, 'items' => []];
        }

        $existingColumns = collect($columns)
            ->filter(fn (string $column): bool => Schema::hasColumn($table, $column))
            ->values()
            ->all();

        if ($existingColumns === [] || ! Schema::hasColumn($table, 'user_id')) {
            return ['available' => false, 'items' => []];
        }

        $query = DB::table($table)->where('user_id', $user->id);

        if ($keywords !== []) {
            $query->where(function ($query) use ($existingColumns, $keywords): void {
                foreach ($keywords as $keyword) {
                    foreach ($existingColumns as $column) {
                        $query->orWhere($column, 'like', '%'.$keyword.'%');
                    }
                }
            });
        }

        $items = $query
            ->orderByDesc(Schema::hasColumn($table, 'updated_at') ? 'updated_at' : 'created_at')
            ->limit($limit)
            ->get()
            ->map(fn (object $record): array => $this->summarizeRecord($record, $existingColumns))
            ->all();

        return ['available' => true, 'items' => $items];
    }

    private function recentMessages(User $user): array
    {
        if (! Schema::hasTable('ai_coach_conversations') || ! Schema::hasTable('ai_coach_messages')) {
            return [];
        }

        return DB::table('ai_coach_messages')
            ->join('ai_coach_conversations', 'ai_coach_conversations.id', '=', 'ai_coach_messages.conversation_id')
            ->where('ai_coach_conversations.user_id', $user->id)
            ->orderByDesc('ai_coach_messages.created_at')
            ->limit(6)
            ->get(['ai_coach_messages.role', 'ai_coach_messages.content', 'ai_coach_messages.created_at'])
            ->map(fn (object $message): array => [
                'role' => $message->role,
                'content_excerpt' => $this->excerpt($message->content, 700),
                'created_at' => $message->created_at,
            ])
            ->all();
    }

    private function summarizeRecord(object $record, array $columns): array
    {
        $summary = [
            'id' => $record->id ?? null,
            'created_at' => $record->created_at ?? null,
            'updated_at' => $record->updated_at ?? null,
        ];

        foreach ($columns as $column) {
            $summary[$column] = $this->excerpt($record->{$column} ?? null);
        }

        return $summary;
    }

    private function excerpt(mixed $value, int $limit = 500): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return mb_substr(trim((string) $value), 0, $limit);
    }
}
