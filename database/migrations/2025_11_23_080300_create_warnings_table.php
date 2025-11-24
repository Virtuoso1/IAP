<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('warnings', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('moderator_id');
            $table->unsignedBigInteger('report_id')->nullable();
            $table->unsignedTinyInteger('warning_level'); // 1-4 severity levels
            $table->unsignedInteger('points')->default(1);
            $table->text('reason');
            $table->boolean('is_active')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('appeal_deadline')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['user_id']);
            $table->index(['moderator_id']);
            $table->index(['report_id']);
            $table->index(['is_active', 'warning_level']);
            $table->index(['expires_at']);

            // Foreign key constraints
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');

            $table->foreign('moderator_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');

            $table->foreign('report_id')
                  ->references('id')
                  ->on('reports')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warnings');
    }
};