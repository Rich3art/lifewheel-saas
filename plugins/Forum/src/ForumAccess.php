<?php

namespace LifeWheel\Plugins\Forum;

use Illuminate\Support\Facades\DB;

final class ForumAccess
{
    public static function blockedBetween(int $firstUserId, int $secondUserId): bool
    {
        return DB::table('social_blocks')
            ->where(function ($query) use ($firstUserId, $secondUserId): void {
                $query->where('blocker_id', $firstUserId)->where('blocked_id', $secondUserId);
            })
            ->orWhere(function ($query) use ($firstUserId, $secondUserId): void {
                $query->where('blocker_id', $secondUserId)->where('blocked_id', $firstUserId);
            })
            ->exists();
    }

    public static function conversationForUser(int $conversationId, int $userId): object
    {
        $conversation = DB::table('social_conversations')
            ->join('social_conversation_participants', 'social_conversation_participants.conversation_id', '=', 'social_conversations.id')
            ->where('social_conversations.id', $conversationId)
            ->where('social_conversation_participants.user_id', $userId)
            ->select('social_conversations.*')
            ->first();

        abort_unless($conversation, 404);

        return $conversation;
    }
}
