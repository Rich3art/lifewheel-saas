<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_providers', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->boolean('enabled')->default(false);
            $table->boolean('sandbox')->default(true);
            $table->json('settings')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamps();
        });

        Schema::create('package_provider_mappings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('package_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_provider_id')->constrained()->cascadeOnDelete();
            $table->string('external_product_id')->nullable();
            $table->string('external_price_id')->nullable();
            $table->string('currency', 3)->nullable();
            $table->unsignedInteger('amount_cents')->nullable();
            $table->boolean('active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['payment_provider_id', 'external_product_id', 'external_price_id'], 'provider_product_price_unique');
        });

        Schema::create('subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('package_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_provider_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider_key');
            $table->string('external_customer_id')->nullable();
            $table->string('external_subscription_id')->nullable();
            $table->string('status')->default('active');
            $table->unsignedInteger('amount_cents')->default(0);
            $table->string('currency', 3)->default('USD');
            $table->string('billing_interval')->default('monthly');
            $table->boolean('trial')->default(false);
            $table->timestamp('current_period_starts_at')->nullable();
            $table->timestamp('current_period_ends_at')->nullable();
            $table->timestamp('cancels_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
            $table->index(['provider_key', 'external_subscription_id']);
        });

        Schema::create('subscription_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider_key');
            $table->string('event_type');
            $table->string('external_event_id')->nullable();
            $table->json('payload_summary')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->unique(['provider_key', 'external_event_id']);
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('billing_invoices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider_key');
            $table->string('external_invoice_id')->nullable();
            $table->string('status')->default('pending');
            $table->unsignedInteger('amount_cents')->default(0);
            $table->string('currency', 3)->default('USD');
            $table->timestamp('paid_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
            $table->index(['provider_key', 'external_invoice_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_invoices');
        Schema::dropIfExists('subscription_events');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('package_provider_mappings');
        Schema::dropIfExists('payment_providers');
    }
};
