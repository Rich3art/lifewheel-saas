<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use LifeWheel\Plugins\Forum\ForumAccess;

require_once dirname(__DIR__).'/src/ForumAccess.php';

Route::middleware(['auth', 'verified', 'twofactor', 'feature:forum.use'])
    ->prefix('app/community')
    ->name('plugins.forum.')
    ->group(function (): void {
        Route::get('/', function () {
            return View::file(dirname(__DIR__).'/resources/views/index.blade.php', [
                'categories' => DB::table('forum_categories')->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
                'topics' => DB::table('forum_topics')
                    ->join('forum_categories', 'forum_categories.id', '=', 'forum_topics.category_id')
                    ->join('users', 'users.id', '=', 'forum_topics.user_id')
                    ->where('forum_topics.status', '!=', 'hidden')
                    ->orderByDesc('forum_topics.is_pinned')
                    ->orderByDesc('forum_topics.last_activity_at')
                    ->limit(20)
                    ->get(['forum_topics.*', 'forum_categories.name as category_name', 'users.name as author_name']),
            ]);
        })->name('index');

        Route::middleware('feature:forum.create_topic')->post('/topics', function (Request $request) {
            $attributes = $request->validate([
                'category_id' => ['required', 'integer', 'exists:forum_categories,id'],
                'title' => ['required', 'string', 'max:180'],
                'body' => ['required', 'string', 'min:10', 'max:10000'],
            ]);

            $category = DB::table('forum_categories')->where('id', $attributes['category_id'])->where('is_active', true)->first();
            abort_unless($category, 404);

            $topicId = DB::table('forum_topics')->insertGetId([
                'category_id' => $category->id,
                'user_id' => $request->user()->id,
                'title' => $attributes['title'],
                'body' => $attributes['body'],
                'status' => 'open',
                'last_activity_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return redirect()->route('plugins.forum.topics.show', $topicId)->with('status', 'topic-created');
        })->name('topics.store');

        Route::get('/topics/{topicId}', function (int $topicId) {
            $topic = forumVisibleTopic($topicId);
            $replies = DB::table('forum_replies')
                ->join('users', 'users.id', '=', 'forum_replies.user_id')
                ->where('topic_id', $topicId)
                ->where('forum_replies.status', 'visible')
                ->orderBy('forum_replies.created_at')
                ->get(['forum_replies.*', 'users.name as author_name']);

            return View::file(dirname(__DIR__).'/resources/views/topic.blade.php', compact('topic', 'replies'));
        })->name('topics.show');

        Route::middleware('feature:forum.reply')->post('/topics/{topicId}/replies', function (Request $request, int $topicId) {
            $topic = forumVisibleTopic($topicId);
            abort_if($topic->status === 'locked', 403);
            $attributes = $request->validate(['body' => ['required', 'string', 'min:2', 'max:10000']]);

            DB::table('forum_replies')->insert([
                'topic_id' => $topic->id,
                'user_id' => $request->user()->id,
                'body' => $attributes['body'],
                'status' => 'visible',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('forum_topics')->where('id', $topic->id)->update(['last_activity_at' => now(), 'updated_at' => now()]);

            return back()->with('status', 'reply-created');
        })->name('replies.store');

        Route::post('/reports', function (Request $request) {
            $attributes = $request->validate([
                'reportable_type' => ['required', 'in:topic,reply,user,message'],
                'reportable_id' => ['required', 'integer', 'min:1'],
                'reason' => ['required', 'string', 'max:120'],
                'details' => ['nullable', 'string', 'max:2000'],
            ]);

            forumAssertReportableVisible($request->user()->id, $attributes['reportable_type'], (int) $attributes['reportable_id']);

            DB::table('social_reports')->insert([
                'reporter_id' => $request->user()->id,
                'reportable_type' => $attributes['reportable_type'],
                'reportable_id' => $attributes['reportable_id'],
                'reason' => $attributes['reason'],
                'details' => $attributes['details'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return back()->with('status', 'report-created');
        })->name('reports.store');

        Route::middleware('feature:forum.follow')->post('/members/{memberId}/follow', function (Request $request, int $memberId) {
            abort_if($memberId === $request->user()->id, 422);
            abort_unless(User::query()->where('id', $memberId)->exists(), 404);
            abort_if(ForumAccess::blockedBetween($request->user()->id, $memberId), 403);

            DB::table('social_follows')->updateOrInsert(
                ['follower_id' => $request->user()->id, 'followed_id' => $memberId],
                ['created_at' => now(), 'updated_at' => now()],
            );

            return back()->with('status', 'followed');
        })->name('members.follow');

        Route::middleware('feature:forum.follow')->delete('/members/{memberId}/follow', function (Request $request, int $memberId) {
            DB::table('social_follows')->where('follower_id', $request->user()->id)->where('followed_id', $memberId)->delete();

            return back()->with('status', 'unfollowed');
        })->name('members.unfollow');

        Route::post('/members/{memberId}/block', function (Request $request, int $memberId) {
            abort_if($memberId === $request->user()->id, 422);
            abort_unless(User::query()->where('id', $memberId)->exists(), 404);

            DB::table('social_blocks')->updateOrInsert(
                ['blocker_id' => $request->user()->id, 'blocked_id' => $memberId],
                ['created_at' => now(), 'updated_at' => now()],
            );
            DB::table('social_follows')->where(function ($query) use ($request, $memberId): void {
                $query->where('follower_id', $request->user()->id)->where('followed_id', $memberId);
            })->orWhere(function ($query) use ($request, $memberId): void {
                $query->where('follower_id', $memberId)->where('followed_id', $request->user()->id);
            })->delete();

            return back()->with('status', 'blocked');
        })->name('members.block');
    });

Route::middleware(['auth', 'verified', 'twofactor', 'feature:forum.message'])
    ->prefix('app/messages')
    ->name('plugins.forum.messages.')
    ->group(function (): void {
        Route::get('/', function (Request $request) {
            $conversations = DB::table('social_conversations')
                ->join('social_conversation_participants', 'social_conversation_participants.conversation_id', '=', 'social_conversations.id')
                ->where('social_conversation_participants.user_id', $request->user()->id)
                ->orderByDesc('social_conversations.updated_at')
                ->paginate(12);

            return View::file(dirname(__DIR__).'/resources/views/messages.blade.php', compact('conversations'));
        })->name('index');

        Route::post('/conversations', function (Request $request) {
            $attributes = $request->validate([
                'recipient_id' => ['required', 'integer', 'exists:users,id'],
                'body' => ['required', 'string', 'min:2', 'max:5000'],
            ]);

            $recipientId = (int) $attributes['recipient_id'];
            abort_if($recipientId === $request->user()->id, 422);
            abort_if(ForumAccess::blockedBetween($request->user()->id, $recipientId), 403);
            $profile = DB::table('forum_profiles')->where('user_id', $recipientId)->first();
            abort_if($profile && ! $profile->allow_messages, 403);

            $conversationId = DB::table('social_conversations')->insertGetId([
                'subject' => 'Direct message',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ([$request->user()->id, $recipientId] as $userId) {
                DB::table('social_conversation_participants')->insert([
                    'conversation_id' => $conversationId,
                    'user_id' => $userId,
                    'last_read_at' => $userId === $request->user()->id ? now() : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('social_messages')->insert([
                'conversation_id' => $conversationId,
                'sender_id' => $request->user()->id,
                'body' => $attributes['body'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return redirect()->route('plugins.forum.messages.show', $conversationId)->with('status', 'message-sent');
        })->name('conversations.store');

        Route::get('/conversations/{conversationId}', function (Request $request, int $conversationId) {
            $conversation = ForumAccess::conversationForUser($conversationId, $request->user()->id);
            DB::table('social_conversation_participants')
                ->where('conversation_id', $conversationId)
                ->where('user_id', $request->user()->id)
                ->update(['last_read_at' => now(), 'updated_at' => now()]);

            $messages = DB::table('social_messages')
                ->join('users', 'users.id', '=', 'social_messages.sender_id')
                ->where('conversation_id', $conversationId)
                ->orderBy('social_messages.created_at')
                ->get(['social_messages.*', 'users.name as sender_name']);

            return View::file(dirname(__DIR__).'/resources/views/conversation.blade.php', compact('conversation', 'messages'));
        })->name('show');

        Route::post('/conversations/{conversationId}/messages', function (Request $request, int $conversationId) {
            ForumAccess::conversationForUser($conversationId, $request->user()->id);
            $attributes = $request->validate(['body' => ['required', 'string', 'min:2', 'max:5000']]);
            $participantIds = DB::table('social_conversation_participants')->where('conversation_id', $conversationId)->pluck('user_id')->all();
            foreach ($participantIds as $participantId) {
                abort_if($participantId !== $request->user()->id && ForumAccess::blockedBetween($request->user()->id, (int) $participantId), 403);
            }

            DB::table('social_messages')->insert([
                'conversation_id' => $conversationId,
                'sender_id' => $request->user()->id,
                'body' => $attributes['body'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('social_conversations')->where('id', $conversationId)->update(['updated_at' => now()]);

            return back()->with('status', 'message-sent');
        })->name('messages.store');
    });

Route::middleware(['auth', 'verified', 'twofactor', 'permission:forum.moderate'])
    ->prefix('admin/forum')
    ->name('plugins.forum.admin.')
    ->group(function (): void {
        Route::get('/reports', function () {
            return View::file(dirname(__DIR__).'/resources/views/admin/reports.blade.php', [
                'reports' => DB::table('social_reports')
                    ->join('users', 'users.id', '=', 'social_reports.reporter_id')
                    ->orderByDesc('social_reports.created_at')
                    ->paginate(20, ['social_reports.*', 'users.name as reporter_name']),
            ]);
        })->name('reports');

        Route::put('/reports/{reportId}', function (Request $request, int $reportId) {
            $attributes = $request->validate(['status' => ['required', 'in:open,reviewing,resolved,dismissed']]);
            $updated = DB::table('social_reports')->where('id', $reportId)->update([
                'status' => $attributes['status'],
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'updated_at' => now(),
            ]);
            abort_unless($updated === 1, 404);

            return back()->with('status', 'report-updated');
        })->name('reports.update');
    });

if (! function_exists('forumVisibleTopic')) {
    function forumVisibleTopic(int $topicId): object
    {
        $topic = DB::table('forum_topics')
            ->join('forum_categories', 'forum_categories.id', '=', 'forum_topics.category_id')
            ->join('users', 'users.id', '=', 'forum_topics.user_id')
            ->where('forum_topics.id', $topicId)
            ->where('forum_topics.status', '!=', 'hidden')
            ->first(['forum_topics.*', 'forum_categories.name as category_name', 'users.name as author_name']);

        abort_unless($topic, 404);

        return $topic;
    }
}

if (! function_exists('forumAssertReportableVisible')) {
    function forumAssertReportableVisible(int $userId, string $type, int $id): void
    {
        $exists = match ($type) {
            'topic' => DB::table('forum_topics')->where('id', $id)->where('status', '!=', 'hidden')->exists(),
            'reply' => DB::table('forum_replies')->where('id', $id)->where('status', 'visible')->exists(),
            'user' => User::query()->where('id', $id)->exists(),
            'message' => DB::table('social_messages')
                ->join('social_conversation_participants', 'social_conversation_participants.conversation_id', '=', 'social_messages.conversation_id')
                ->where('social_messages.id', $id)
                ->where('social_conversation_participants.user_id', $userId)
                ->exists(),
            default => false,
        };

        abort_unless($exists, 404);
    }
}
