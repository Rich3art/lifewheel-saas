<?php

use App\Services\AI\AiGateway;
use App\Services\AI\AiRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use LifeWheel\Plugins\AiLifeAnalysis\AnalysisSchema;
use LifeWheel\Plugins\AiLifeAnalysis\LifeContextBuilder;

require_once dirname(__DIR__).'/src/AnalysisSchema.php';
require_once dirname(__DIR__).'/src/LifeContextBuilder.php';

Route::middleware(['auth', 'verified', 'twofactor', 'feature:ai.analysis'])
    ->prefix('app/ai-life-analysis')
    ->name('plugins.ai-life-analysis.')
    ->group(function (): void {
        Route::get('/', function (Request $request) {
            return View::file(dirname(__DIR__).'/resources/views/index.blade.php', [
                'analyses' => DB::table('ai_life_analyses')
                    ->where('user_id', $request->user()->id)
                    ->latest()
                    ->paginate(10),
                'latest' => DB::table('ai_life_analyses')
                    ->where('user_id', $request->user()->id)
                    ->latest()
                    ->first(),
            ]);
        })->name('index');

        Route::post('/analyses', function (Request $request, AiGateway $gateway, LifeContextBuilder $builder) {
            $context = $builder->build($request->user());

            abort_if(count($context['lifewheel']['assessments']) === 0, 422, 'Complete a LifeWheel assessment before requesting AI analysis.');

            $response = $gateway->generate(new AiRequest(
                featureSlug: 'ai.analysis',
                systemPrompt: aiLifeAnalysisSystemPrompt(),
                userPrompt: "Analyze this normalized LifeWheel context. Return only the required JSON schema.\n\n".json_encode($context, JSON_PRETTY_PRINT),
                responseSchema: AnalysisSchema::jsonSchema(),
                user: $request->user(),
                metadata: ['source' => 'ai-life-analysis-plugin'],
            ));

            $analysisId = DB::table('ai_life_analyses')->insertGetId([
                'user_id' => $request->user()->id,
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

            return redirect()->route('plugins.ai-life-analysis.show', $analysisId)->with('status', 'analysis-created');
        })->name('analyses.store');

        Route::get('/analyses/{analysisId}', function (Request $request, int $analysisId) {
            $analysis = DB::table('ai_life_analyses')
                ->where('id', $analysisId)
                ->where('user_id', $request->user()->id)
                ->first();

            abort_unless($analysis, 404);
            $analysis->structured = json_decode((string) $analysis->structured, true) ?: [];

            return View::file(dirname(__DIR__).'/resources/views/show.blade.php', [
                'analysis' => $analysis,
            ]);
        })->name('show');
    });

if (! function_exists('aiLifeAnalysisSystemPrompt')) {
    function aiLifeAnalysisSystemPrompt(): string
    {
        return implode("\n", [
            'You are an executive life analyst for LifeWheel SaaS.',
            'Use only the normalized user context provided.',
            'Be specific, warm, and measurable.',
            'Do not claim causation from correlation.',
            'Do not provide medical, legal, financial, or religious certainty.',
            'Focus on patterns, risks, opportunities, and next actions.',
            'Return strict JSON matching the schema.',
        ]);
    }
}
