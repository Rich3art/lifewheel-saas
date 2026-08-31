<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_providers', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->boolean('enabled')->default(false);
            $table->boolean('mock_mode')->default(true);
            $table->text('encrypted_api_key')->nullable();
            $table->string('base_url')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_model_routes', function (Blueprint $table): void {
            $table->id();
            $table->string('feature_slug');
            $table->foreignId('ai_provider_id')->nullable()->constrained('ai_providers')->nullOnDelete();
            $table->string('model');
            $table->boolean('enabled')->default(true);
            $table->unsignedInteger('monthly_limit')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['feature_slug', 'sort_order']);
        });

        Schema::create('ai_usage_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('feature_slug');
            $table->string('provider_key');
            $table->string('model');
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->unsignedInteger('estimated_cost_cents')->default(0);
            $table->string('status')->default('succeeded');
            $table->string('request_hash')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'feature_slug', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_usage_events');
        Schema::dropIfExists('ai_model_routes');
        Schema::dropIfExists('ai_providers');
    }
};
