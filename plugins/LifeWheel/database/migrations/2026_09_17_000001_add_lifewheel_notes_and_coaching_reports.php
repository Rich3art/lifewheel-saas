<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('lifewheel_scores') && ! Schema::hasColumn('lifewheel_scores', 'note')) {
            Schema::table('lifewheel_scores', function (Blueprint $table): void {
                $table->text('note')->nullable()->after('score');
            });
        }

        if (! Schema::hasTable('lifewheel_coaching_reports')) {
            Schema::create('lifewheel_coaching_reports', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('assessment_id')->constrained('lifewheel_assessments')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('provider_key')->default('lifeos');
                $table->string('model')->default('category-coach-v1');
                $table->json('content');
                $table->timestamps();
                $table->unique('assessment_id');
                $table->index(['user_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lifewheel_coaching_reports');

        if (Schema::hasTable('lifewheel_scores') && Schema::hasColumn('lifewheel_scores', 'note')) {
            Schema::table('lifewheel_scores', function (Blueprint $table): void {
                $table->dropColumn('note');
            });
        }
    }
};
