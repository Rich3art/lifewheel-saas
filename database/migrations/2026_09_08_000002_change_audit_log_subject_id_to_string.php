<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('audit_logs') || ! Schema::hasColumn('audit_logs', 'subject_id')) {
            return;
        }

        DB::statement('ALTER TABLE audit_logs MODIFY subject_id VARCHAR(255) NULL');
    }

    public function down(): void
    {
        if (! Schema::hasTable('audit_logs') || ! Schema::hasColumn('audit_logs', 'subject_id')) {
            return;
        }

        DB::statement('ALTER TABLE audit_logs MODIFY subject_id BIGINT UNSIGNED NULL');
    }
};
