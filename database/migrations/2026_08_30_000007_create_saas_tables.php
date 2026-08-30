<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('features', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->string('source')->default('core');
            $table->timestamps();
        });

        Schema::create('packages', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('short_description')->nullable();
            $table->boolean('active')->default(true);
            $table->boolean('public')->default(true);
            $table->boolean('featured')->default(false);
            $table->unsignedInteger('price_cents')->default(0);
            $table->string('currency', 3)->default('USD');
            $table->string('billing_interval')->default('monthly');
            $table->unsignedSmallInteger('trial_days')->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('cta_label')->nullable();
            $table->string('landing_page_slug')->nullable();
            $table->json('seo')->nullable();
            $table->timestamps();
        });

        Schema::create('feature_package', function (Blueprint $table): void {
            $table->foreignId('feature_id')->constrained()->cascadeOnDelete();
            $table->foreignId('package_id')->constrained()->cascadeOnDelete();
            $table->boolean('enabled')->default(true);
            $table->timestamps();
            $table->primary(['feature_id', 'package_id']);
        });

        Schema::create('package_limits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('package_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->string('value');
            $table->timestamps();
            $table->unique(['package_id', 'key']);
        });

        Schema::create('user_packages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('package_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('active');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });

        Schema::create('user_feature_overrides', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('feature_id')->constrained()->cascadeOnDelete();
            $table->boolean('enabled');
            $table->text('reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'feature_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_feature_overrides');
        Schema::dropIfExists('user_packages');
        Schema::dropIfExists('package_limits');
        Schema::dropIfExists('feature_package');
        Schema::dropIfExists('packages');
        Schema::dropIfExists('features');
    }
};
