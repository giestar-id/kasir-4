<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('screenshot_imports', function (Blueprint $table) {
            $table->id();
            $table->string('file_path');
            $table->string('file_hash')->unique();
            $table->string('status')->default('pending'); // pending, processing, needs_review, ready_to_save, saved, duplicate, failed
            $table->string('source')->nullable(); // ai_api, ai_local
            $table->string('platform')->nullable(); // gofood, shopeefood
            $table->json('raw_result')->nullable();
            $table->integer('confidence')->nullable();
            $table->text('fallback_reason')->nullable();
            $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        Schema::create('ai_call_logs', function (Blueprint $table) {
            $table->id();
            $table->string('provider');
            $table->string('model')->nullable();
            $table->string('status');
            $table->integer('latency_ms')->default(0);
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedBigInteger('est_cost')->default(0);
            $table->timestamps();

            $table->index('created_at');
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('meta')->nullable();
            $table->string('ip')->nullable();
            $table->timestamps();

            $table->index(['subject_type', 'subject_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('ai_call_logs');
        Schema::dropIfExists('screenshot_imports');
    }
};
