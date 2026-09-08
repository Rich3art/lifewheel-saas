<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('privacy_requests', function (Blueprint $table): void {
            $table->timestamp('identity_confirmed_by_user_at')->nullable()->after('details');
            $table->timestamp('due_at')->nullable()->after('identity_confirmed_at');
            $table->json('resolution_summary')->nullable()->after('admin_notes');
        });

        Schema::table('data_exports', function (Blueprint $table): void {
            $table->timestamp('completed_at')->nullable()->after('expires_at');
        });

        Schema::create('privacy_consents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->boolean('granted')->default(false);
            $table->timestamp('granted_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'key']);
        });

        Schema::create('policy_acceptances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->foreignId('page_version_id')->constrained('page_versions')->cascadeOnDelete();
            $table->timestamp('accepted_at');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'page_version_id']);
        });

        Schema::create('privacy_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->string('value')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('privacy_settings');
        Schema::dropIfExists('policy_acceptances');
        Schema::dropIfExists('privacy_consents');

        Schema::table('data_exports', function (Blueprint $table): void {
            $table->dropColumn('completed_at');
        });

        Schema::table('privacy_requests', function (Blueprint $table): void {
            $table->dropColumn(['identity_confirmed_by_user_at', 'due_at', 'resolution_summary']);
        });
    }
};
