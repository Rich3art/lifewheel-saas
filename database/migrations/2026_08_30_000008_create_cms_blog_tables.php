<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('status')->default('draft');
            $table->longText('body')->nullable();
            $table->string('seo_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('canonical_url')->nullable();
            $table->json('open_graph')->nullable();
            $table->boolean('is_legal')->default(false);
            $table->unsignedBigInteger('current_version_id')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'published_at']);
        });

        Schema::create('page_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('title');
            $table->longText('body')->nullable();
            $table->string('seo_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('canonical_url')->nullable();
            $table->json('open_graph')->nullable();
            $table->string('status');
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['page_id', 'version']);
        });

        Schema::table('pages', function (Blueprint $table): void {
            $table->foreign('current_version_id')->references('id')->on('page_versions')->nullOnDelete();
        });

        Schema::create('blog_posts', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('body')->nullable();
            $table->string('status')->default('draft');
            $table->string('featured_image_path')->nullable();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('seo_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('canonical_url')->nullable();
            $table->json('open_graph')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'published_at']);
        });

        Schema::create('blog_post_revisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('blog_post_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('excerpt')->nullable();
            $table->longText('body')->nullable();
            $table->string('status');
            $table->string('seo_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('canonical_url')->nullable();
            $table->json('open_graph')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('blog_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('blog_tags', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('blog_category_post', function (Blueprint $table): void {
            $table->foreignId('blog_category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('blog_post_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['blog_category_id', 'blog_post_id']);
        });

        Schema::create('blog_post_tag', function (Blueprint $table): void {
            $table->foreignId('blog_post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('blog_tag_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['blog_post_id', 'blog_tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_post_tag');
        Schema::dropIfExists('blog_category_post');
        Schema::dropIfExists('blog_tags');
        Schema::dropIfExists('blog_categories');
        Schema::dropIfExists('blog_post_revisions');
        Schema::dropIfExists('blog_posts');
        Schema::table('pages', fn (Blueprint $table) => $table->dropForeign(['current_version_id']));
        Schema::dropIfExists('page_versions');
        Schema::dropIfExists('pages');
    }
};
