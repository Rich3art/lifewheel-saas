<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('habits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type')->default('positive');
            $table->json('areas')->nullable();
            $table->json('weekdays')->nullable();
            $table->unsignedSmallInteger('target_count')->default(1);
            $table->decimal('target_value', 10, 2)->nullable();
            $table->string('unit', 40)->nullable();
            $table->string('status')->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });

        Schema::create('habit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('habit_id')->constrained('habits')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('logged_on');
            $table->boolean('completed')->default(false);
            $table->decimal('value', 10, 2)->nullable();
            $table->string('mood')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['habit_id', 'logged_on']);
            $table->index(['user_id', 'logged_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('habit_logs');
        Schema::dropIfExists('habits');
    }
};
