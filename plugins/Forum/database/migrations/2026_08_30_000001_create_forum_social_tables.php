<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forum_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('display_name')->nullable();
            $table->text('bio')->nullable();
            $table->boolean('discoverable')->default(true);
            $table->boolean('show_followers')->default(true);
            $table->boolean('allow_messages')->default(true);
            $table->timestamps();
        });

        Schema::create('forum_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(100);
            $table->timestamps();
        });

        Schema::create('forum_topics', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->constrained('forum_categories')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->longText('body');
            $table->string('status')->default('open');
            $table->boolean('is_pinned')->default(false);
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();
            $table->index(['category_id', 'status', 'last_activity_at']);
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('forum_replies', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('topic_id')->constrained('forum_topics')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->longText('body');
            $table->string('status')->default('visible');
            $table->timestamps();
            $table->index(['topic_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('social_follows', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('follower_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('followed_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['follower_id', 'followed_id']);
        });

        Schema::create('social_blocks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('blocker_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('blocked_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['blocker_id', 'blocked_id']);
        });

        Schema::create('social_conversations', function (Blueprint $table): void {
            $table->id();
            $table->string('subject')->nullable();
            $table->timestamps();
        });

        Schema::create('social_conversation_participants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('conversation_id')->constrained('social_conversations')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('last_read_at')->nullable();
            $table->timestamps();
            $table->unique(['conversation_id', 'user_id']);
        });

        Schema::create('social_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('conversation_id')->constrained('social_conversations')->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->longText('body');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['conversation_id', 'created_at']);
            $table->index(['sender_id', 'created_at']);
        });

        Schema::create('social_reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('reporter_id')->constrained('users')->cascadeOnDelete();
            $table->string('reportable_type');
            $table->unsignedBigInteger('reportable_id');
            $table->string('reason');
            $table->text('details')->nullable();
            $table->string('status')->default('open');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->index(['reportable_type', 'reportable_id']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_reports');
        Schema::dropIfExists('social_messages');
        Schema::dropIfExists('social_conversation_participants');
        Schema::dropIfExists('social_conversations');
        Schema::dropIfExists('social_blocks');
        Schema::dropIfExists('social_follows');
        Schema::dropIfExists('forum_replies');
        Schema::dropIfExists('forum_topics');
        Schema::dropIfExists('forum_categories');
        Schema::dropIfExists('forum_profiles');
    }
};
