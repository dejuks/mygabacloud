<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_files', function (Blueprint $table) {
            // Deterministic checks — no AI, no external service, always run.
            $table->enum('integrity_status', ['pending', 'valid', 'corrupted'])->default('pending')->after('scan_status');
            $table->foreignId('duplicate_of_file_id')->nullable()->after('integrity_status')
                  ->constrained('product_files')->nullOnDelete();

            // Optional, AI-assisted, advisory only — never a pass/fail gate.
            $table->text('ai_review_notes')->nullable()->after('duplicate_of_file_id');
            $table->timestamp('analyzed_at')->nullable()->after('ai_review_notes');
        });
    }

    public function down(): void
    {
        Schema::table('product_files', function (Blueprint $table) {
            $table->dropConstrainedForeignId('duplicate_of_file_id');
            $table->dropColumn(['integrity_status', 'ai_review_notes', 'analyzed_at']);
        });
    }
};
