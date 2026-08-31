<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lessons', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->longText('body');
            $table->json('areas')->nullable();
            $table->string('source_type')->default('manual');
            $table->string('source_id')->nullable();
            $table->string('idempotency_key')->nullable();
            $table->date('learned_on');
            $table->timestamps();
            $table->unique(['user_id', 'idempotency_key']);
            $table->index(['user_id', 'learned_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
