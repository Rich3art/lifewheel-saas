<?php

namespace App\Services\Privacy;

use App\Models\DataExport;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

final class PrivacyExportService
{
    /**
     * @var array<string, array<int, string>>
     */
    private array $tables = [
        'core' => [
            'privacy_requests' => 'user_id',
            'data_exports' => 'user_id',
            'privacy_consents' => 'user_id',
            'policy_acceptances' => 'user_id',
            'user_packages' => 'user_id',
            'subscriptions' => 'user_id',
            'billing_invoices' => 'user_id',
            'ai_usage_events' => 'user_id',
        ],
        'lifewheel' => ['lifewheel_assessments' => 'user_id', 'lifewheel_scores' => 'user_id'],
        'journal' => ['journal_entries' => 'user_id'],
        'goals' => ['goals' => 'user_id', 'goal_milestones' => 'user_id', 'goal_progress_records' => 'user_id'],
        'habits' => ['habits' => 'user_id', 'habit_logs' => 'user_id'],
        'projects' => ['projects' => 'user_id', 'project_tasks' => 'user_id'],
        'lessons' => ['lessons' => 'user_id'],
        'ai' => ['ai_life_analyses' => 'user_id', 'ai_reviews' => 'user_id', 'ai_coach_conversations' => 'user_id', 'ai_coach_messages' => 'user_id'],
        'gamification' => ['xp_events' => 'user_id'],
        'community' => [
            'forum_profiles' => 'user_id',
            'forum_topics' => 'user_id',
            'forum_replies' => 'user_id',
            'social_follows' => 'follower_id',
            'social_blocks' => 'blocker_id',
            'social_messages' => 'sender_id',
            'social_reports' => 'reporter_id',
        ],
    ];

    public function generate(DataExport $export): DataExport
    {
        $export->loadMissing('user', 'privacyRequest');
        $user = $export->user;
        $payload = [
            'generated_at' => now()->toIso8601String(),
            'export_id' => $export->id,
            'privacy_request_id' => $export->privacy_request_id,
            'user' => $this->safeUser($user),
            'data' => $this->collectTables($user),
        ];

        $directory = storage_path('app/private/privacy_exports/user-'.$user->id);
        File::ensureDirectoryExists($directory, 0750, true);

        $path = $directory.'/export-'.$export->id.'.json';
        File::put($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $export->forceFill([
            'status' => 'ready',
            'format' => 'json',
            'path' => $path,
            'expires_at' => now()->addDays($this->exportExpiryDays()),
            'completed_at' => now(),
        ])->save();

        return $export->fresh();
    }

    private function safeUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'username' => $user->username,
            'timezone' => $user->timezone,
            'email_verified_at' => $user->email_verified_at?->toIso8601String(),
            'created_at' => $user->created_at?->toIso8601String(),
            'updated_at' => $user->updated_at?->toIso8601String(),
        ];
    }

    private function collectTables(User $user): array
    {
        $data = [];

        foreach ($this->tables as $group => $tables) {
            foreach ($tables as $table => $ownerColumn) {
                if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $ownerColumn)) {
                    continue;
                }

                $data[$group][$table] = DB::table($table)
                    ->where($ownerColumn, $user->id)
                    ->orderBy('id')
                    ->get()
                    ->map(fn (object $row) => $this->safeRow($table, (array) $row))
                    ->all();
            }
        }

        return $data;
    }

    private function safeRow(string $table, array $row): array
    {
        if ($table === 'data_exports') {
            unset($row['path']);
        }

        return $row;
    }

    private function exportExpiryDays(): int
    {
        $value = DB::table('privacy_settings')->where('key', 'export_expiry_days')->value('value');

        return max(1, min(30, (int) ($value ?: 7)));
    }
}
