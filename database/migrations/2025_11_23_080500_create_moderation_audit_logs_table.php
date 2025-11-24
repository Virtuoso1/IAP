<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('moderation_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->char('log_hash', 64)->unique(); // SHA-256 hash of entire record
            $table->char('previous_log_hash', 64)->nullable(); // Blockchain-like chaining
            $table->string('event_type', 100);
            $table->enum('actor_type', ['user', 'moderator', 'system', 'api']);
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('target_type', 100); // 'Report', 'Warning', 'User', etc.
            $table->unsignedBigInteger('target_id')->nullable();
            $table->string('action', 100);
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('metadata')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('timestamp', 6)->default(DB::raw('CURRENT_TIMESTAMP(6)'));

            // Indexes for performance
            $table->index(['event_type']);
            $table->index(['actor_type', 'actor_id']);
            $table->index(['target_type', 'target_id']);
            $table->index(['timestamp']);
            $table->index(['previous_log_hash', 'log_hash']);

            // Foreign key constraints
            $table->foreign('actor_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('moderation_audit_logs');
    }
};