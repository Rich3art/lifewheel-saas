<?php

use App\Services\AI\AiGateway;
use App\Services\AI\AiRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Validation\Rule;
use LifeWheel\Plugins\AiReviews\ReviewContextBuilder;
use LifeWheel\Plugins\AiReviews\ReviewPeriod;
use LifeWheel\Plugins\AiReviews\ReviewResponseSchema;

require_once dirname(__DIR__).'/src/ReviewContextBuilder.php';
require_once dirname(__DIR__).'/src/ReviewPeriod.php';
require_once dirname(__DIR__).'/src/ReviewResponseSchema.php';

Route::middleware(['auth', 'verified', 'twofactor', 'feature:ai.reviews'])
    ->prefix('app/ai-reviews')
    ->name('plugins.ai-reviews.')
    ->group(function (): void {
        Route::get('/', function (Request $request) {
            return View::file(dirname(__DIR__).'/resources/views/index.blade.php', [
                'reviews' => DB::table('ai_reviews')
                    ->where('user_id', $request->user()->id)
                    ->orderByDesc('period_start')
                    ->orderByDesc('created_at')
                    ->paginate(12),
                'periodTypes' => ReviewPeriod::TYPES,
            ]);
        })->name('index');

        Route::post('/reviews', function (Request $request, AiGateway $gateway, ReviewContextBuilder $builder) {
            $validated = $request->validate([
                'period_type' => ['required', Rule::in(ReviewPeriod::TYPES)],
                'start_date' => ['nullable', 'date', 'required_if:period_type,custom'],
                'end_date' => ['nullable', 'date', 'required_if:period_type,custom'],
            ]);

            try {
                [$start, $end] = ReviewPeriod::range(
                    $validated['period_type'],
                    $validated['start_date'] ?? null,
                    $validated['end_date'] ?? null,
                );
            } catch (\InvalidArgumentException $exception) {
                return back()->withErrors(['period_type' => $exception->getMessage()])->withInput();
            }

            $context = $builder->build($request->user(), $validated['period_type'], $start, $end);
            $response = $gateway->generate(new AiRequest(
                featureSlug: 'ai.reviews',
                systemPrompt: aiReviewsSystemPrompt(),
                userPrompt: "Create an executive life review for this period using only the normalized private context. Return strict JSON matching the schema.\n\n".json_encode($context, JSON_PRETTY_PRINT),
                responseSchema: ReviewResponseSchema::jsonSchema(),
                user: $request->user(),
                metadata: ['source' => 'ai-reviews-plugin', 'period_type' => $validated['period_type']],
            ));

            $reviewId = DB::table('ai_reviews')->insertGetId([
                'user_id' => $request->user()->id,
                'period_type' => $validated['period_type'],
                'period_start' => $start->toDateString(),
                'period_end' => $end->toDateString(),
                'status' => 'completed',
                'context_summary' => json_encode($context),
                'content' => $response->content,
                'structured' => json_encode($response->structured),
                'provider_key' => $response->providerKey,
                'model' => $response->model,
                'input_tokens' => $response->inputTokens,
                'output_tokens' => $response->outputTokens,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return redirect()->route('plugins.ai-reviews.show', $reviewId)->with('status', 'review-created');
        })->name('reviews.store');

        Route::get('/reviews/{reviewId}', function (Request $request, int $reviewId) {
            $review = DB::table('ai_reviews')
                ->where('id', $reviewId)
                ->where('user_id', $request->user()->id)
                ->first();

            abort_unless($review, 404);
            $review->structured = json_decode((string) $review->structured, true) ?: [];

            return View::file(dirname(__DIR__).'/resources/views/show.blade.php', [
                'review' => $review,
            ]);
        })->name('show');
    });

if (! function_exists('aiReviewsSystemPrompt')) {
    function aiReviewsSystemPrompt(): string
    {
        return implode("\n", [
            'You are an executive life-review coach inside LifeWheel SaaS.',
            'Review only the supplied period and user-owned context.',
            'Be warm, direct, concrete, and measurable.',
            'Separate evidence from interpretation.',
            'Do not claim medical, legal, financial, or religious certainty.',
            'Acknowledge limited data when the context is sparse.',
            'Return strict JSON matching the schema.',
        ]);
    }
}
