<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gamification_rules', function (Blueprint $table): void {
            $table->id();
            $table->string('event_type')->unique();
            $table->string('label');
            $table->integer('xp');
            $table->boolean('enabled')->default(true);
            $table->unsignedInteger('cooldown_hours')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('xp_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('event_type');
            $table->string('source_type');
            $table->string('source_id');
            $table->integer('xp');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'event_type', 'source_type', 'source_id'], 'xp_events_idempotency_unique');
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('xp_events');
        Schema::dropIfExists('gamification_rules');
    }
};
