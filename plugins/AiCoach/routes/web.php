<?php

use App\Services\AI\AiGateway;
use App\Services\AI\AiRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use LifeWheel\Plugins\AiCoach\CoachContextBuilder;
use LifeWheel\Plugins\AiCoach\CoachResponseSchema;

require_once dirname(__DIR__).'/src/CoachContextBuilder.php';
require_once dirname(__DIR__).'/src/CoachResponseSchema.php';

Route::middleware(['auth', 'verified', 'twofactor', 'feature:ai.coach'])
    ->prefix('app/ai-coach')
    ->name('plugins.ai-coach.')
    ->group(function (): void {
        Route::get('/', function (Request $request) {
            $conversations = DB::table('ai_coach_conversations')
                ->where('user_id', $request->user()->id)
                ->orderByDesc('updated_at')
                ->paginate(12);

            return View::file(dirname(__DIR__).'/resources/views/index.blade.php', [
                'conversations' => $conversations,
            ]);
        })->name('index');

        Route::post('/conversations', function (Request $request) {
            $validated = $request->validate([
                'question' => ['required', 'string', 'min:8', 'max:1000'],
            ]);

            $conversationId = DB::table('ai_coach_conversations')->insertGetId([
                'user_id' => $request->user()->id,
                'title' => aiCoachTitle($validated['question']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return redirect()
                ->route('plugins.ai-coach.conversations.show', $conversationId)
                ->withInput(['question' => $validated['question']]);
        })->name('conversations.store');

        Route::get('/conversations/{conversationId}', function (Request $request, int $conversationId) {
            $conversation = aiCoachConversationForUser($request, $conversationId);

            return View::file(dirname(__DIR__).'/resources/views/show.blade.php', [
                'conversation' => $conversation,
                'messages' => aiCoachMessages($conversation->id),
                'draftQuestion' => old('question'),
            ]);
        })->name('conversations.show');

        Route::post('/conversations/{conversationId}/messages', function (Request $request, int $conversationId, AiGateway $gateway, CoachContextBuilder $builder) {
            $conversation = aiCoachConversationForUser($request, $conversationId);
            $validated = $request->validate([
                'question' => ['required', 'string', 'min:8', 'max:1000'],
            ]);

            $question = $validated['question'];
            $context = $builder->build($request->user(), $question);

            DB::table('ai_coach_messages')->insert([
                'conversation_id' => $conversation->id,
                'user_id' => $request->user()->id,
                'role' => 'user',
                'content' => $question,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $response = $gateway->generate(new AiRequest(
                featureSlug: 'ai.coach',
                systemPrompt: aiCoachSystemPrompt(),
                userPrompt: "Answer this user question using only the selective private context. Return strict JSON matching the schema.\n\n".json_encode($context, JSON_PRETTY_PRINT),
                responseSchema: CoachResponseSchema::jsonSchema(),
                user: $request->user(),
                metadata: ['source' => 'ai-coach-plugin', 'conversation_id' => $conversation->id],
            ));

            DB::table('ai_coach_messages')->insert([
                'conversation_id' => $conversation->id,
                'user_id' => $request->user()->id,
                'role' => 'assistant',
                'content' => $response->content,
                'structured' => json_encode($response->structured),
                'context_summary' => json_encode($context),
                'provider_key' => $response->providerKey,
                'model' => $response->model,
                'input_tokens' => $response->inputTokens,
                'output_tokens' => $response->outputTokens,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('ai_coach_conversations')->where('id', $conversation->id)->update([
                'updated_at' => now(),
            ]);

            return redirect()->route('plugins.ai-coach.conversations.show', $conversation->id);
        })->name('messages.store');
    });

if (! function_exists('aiCoachConversationForUser')) {
    function aiCoachConversationForUser(Request $request, int $conversationId): object
    {
        $conversation = DB::table('ai_coach_conversations')
            ->where('id', $conversationId)
            ->where('user_id', $request->user()->id)
            ->first();

        abort_unless($conversation, 404);

        return $conversation;
    }
}

if (! function_exists('aiCoachMessages')) {
    function aiCoachMessages(int $conversationId): \Illuminate\Support\Collection
    {
        return DB::table('ai_coach_messages')
            ->where('conversation_id', $conversationId)
            ->orderBy('created_at')
            ->get()
            ->map(function (object $message): object {
                $message->structured = json_decode((string) $message->structured, true) ?: null;

                return $message;
            });
    }
}

if (! function_exists('aiCoachTitle')) {
    function aiCoachTitle(string $question): string
    {
        return mb_substr(trim($question), 0, 80);
    }
}

if (! function_exists('aiCoachSystemPrompt')) {
    function aiCoachSystemPrompt(): string
    {
        return implode("\n", [
            'You are the private executive life coach inside LifeWheel SaaS.',
            'Answer only from the selective user context provided and clearly say when evidence is limited.',
            'Be specific, warm, practical, and measurable.',
            'Do not expose internal IDs or hidden metadata.',
            'Do not provide medical, legal, financial, or religious certainty.',
            'Do not invent facts or imply you accessed data that is not present in context.',
            'Return strict JSON matching the schema.',
        ]);
    }
}
