<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lifewheel_assessments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('overall_score', 4, 2)->default(0);
            $table->text('reflection')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('lifewheel_scores', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('assessment_id')->constrained('lifewheel_assessments')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('area_key');
            $table->string('area_name');
            $table->string('area_group');
            $table->unsignedTinyInteger('score');
            $table->timestamps();
            $table->unique(['assessment_id', 'area_key']);
            $table->index(['user_id', 'area_key', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lifewheel_scores');
        Schema::dropIfExists('lifewheel_assessments');
    }
};
