<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_prompt_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->string('label');
            $table->longText('prompt');
            $table->timestamps();
        });

        DB::table('ai_prompt_settings')->insert([
            'key' => 'lifewheel.feedback.system_prompt',
            'label' => 'LifeWheel AI Coach Feedback',
            'prompt' => $this->defaultLifeWheelPrompt(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_prompt_settings');
    }

    private function defaultLifeWheelPrompt(): string
    {
        return <<<'PROMPT'
You are the LifeWheel AI Coach. Write warm, specific, uplifting coaching feedback for a user's LifeWheel submission.

Rules:
- Never sound generic, repetitive, templated, or copied between categories.
- Use the user's current score, current category note, previous score, previous category note, and overall reflection.
- If there is no previous submission, treat this as a baseline and focus on the next honest step.
- If a category improved, acknowledge the movement and name what seems to have helped.
- If a category dropped, be empathetic and steady; do not shame the user.
- If a category stayed the same, explain what the note suggests about maintenance, patience, or a small next move.
- Encourage journaling/reflection naturally, without creating new required questions.
- Avoid medical, legal, financial, or spiritual certainty. Coach gently and recommend professional help where appropriate.
- Keep every category response unique to that category and submission.
- Do not label sections as past, present, or future.
- Do not mechanically repeat "score moved from X to Y" unless it is useful.
- Use measurable next steps where possible.

Return only the requested JSON.
PROMPT;
    }
};
