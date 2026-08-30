<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plugins', function (Blueprint $table): void {
            $table->string('plugin_id')->primary();
            $table->string('name');
            $table->string('version');
            $table->string('author')->nullable();
            $table->text('description')->nullable();
            $table->string('path');
            $table->string('status')->default('disabled');
            $table->json('manifest');
            $table->timestamp('installed_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('deactivated_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });

        Schema::create('plugin_permission_registrations', function (Blueprint $table): void {
            $table->id();
            $table->string('plugin_id');
            $table->string('slug');
            $table->json('manifest');
            $table->timestamps();

            $table->foreign('plugin_id')->references('plugin_id')->on('plugins')->cascadeOnDelete();
            $table->unique(['plugin_id', 'slug']);
        });

        Schema::create('plugin_feature_registrations', function (Blueprint $table): void {
            $table->id();
            $table->string('plugin_id');
            $table->string('slug');
            $table->json('manifest');
            $table->timestamps();

            $table->foreign('plugin_id')->references('plugin_id')->on('plugins')->cascadeOnDelete();
            $table->unique(['plugin_id', 'slug']);
        });

        Schema::create('plugin_menu_registrations', function (Blueprint $table): void {
            $table->id();
            $table->string('plugin_id');
            $table->string('slug');
            $table->json('manifest');
            $table->timestamps();

            $table->foreign('plugin_id')->references('plugin_id')->on('plugins')->cascadeOnDelete();
            $table->unique(['plugin_id', 'slug']);
        });

        Schema::create('plugin_settings_section_registrations', function (Blueprint $table): void {
            $table->id();
            $table->string('plugin_id');
            $table->string('slug');
            $table->json('manifest');
            $table->timestamps();

            $table->foreign('plugin_id')->references('plugin_id')->on('plugins')->cascadeOnDelete();
            $table->unique(['plugin_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plugin_settings_section_registrations');
        Schema::dropIfExists('plugin_menu_registrations');
        Schema::dropIfExists('plugin_feature_registrations');
        Schema::dropIfExists('plugin_permission_registrations');
        Schema::dropIfExists('plugins');
    }
};
