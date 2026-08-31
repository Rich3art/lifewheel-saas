<?php

namespace LifeWheel\Plugins\Gamification;

use Illuminate\Support\Facades\DB;

final class XpLedger
{
    public function award(int $userId, string $eventType, string $sourceType, string $sourceId, int $xp, array $metadata = []): bool
    {
        if ($xp === 0) {
            return false;
        }

        $exists = DB::table('xp_events')
            ->where('user_id', $userId)
            ->where('event_type', $eventType)
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->exists();

        if ($exists) {
            return false;
        }

        DB::table('xp_events')->insert([
            'user_id' => $userId,
            'event_type' => $eventType,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'xp' => $xp,
            'metadata' => json_encode($metadata),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return true;
    }

    public function totalFor(int $userId): int
    {
        return (int) DB::table('xp_events')->where('user_id', $userId)->sum('xp');
    }
}
