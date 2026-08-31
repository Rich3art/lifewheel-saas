<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('why')->nullable();
            $table->json('areas')->nullable();
            $table->string('status')->default('active');
            $table->text('success_criterion')->nullable();
            $table->string('measure')->nullable();
            $table->decimal('baseline', 12, 2)->nullable();
            $table->decimal('current', 12, 2)->nullable();
            $table->decimal('target', 12, 2)->nullable();
            $table->string('unit', 40)->nullable();
            $table->date('due_date')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });

        Schema::create('goal_milestones', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('goal_id')->constrained('goals')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('notes')->nullable();
            $table->date('due_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'completed_at']);
        });

        Schema::create('goal_progress_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('goal_id')->constrained('goals')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('value', 12, 2);
            $table->text('notes')->nullable();
            $table->date('recorded_on');
            $table->timestamps();
            $table->index(['user_id', 'recorded_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goal_progress_records');
        Schema::dropIfExists('goal_milestones');
        Schema::dropIfExists('goals');
    }
};
