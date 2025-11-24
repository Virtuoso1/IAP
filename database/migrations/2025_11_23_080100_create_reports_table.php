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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('reporter_id');
            $table->unsignedBigInteger('reported_user_id');
            $table->string('reportable_type', 255); // Polymorphic: 'User', 'Message', 'GroupMessage', 'Group'
            $table->unsignedBigInteger('reportable_id');
            $table->unsignedBigInteger('category_id');
            $table->enum('severity', ['low', 'medium', 'high', 'critical']);
            $table->text('description')->nullable();
            $table->enum('status', ['pending', 'under_review', 'resolved', 'dismissed', 'escalated'])->default('pending');
            $table->decimal('priority_score', 5, 2)->default(0.00);
            $table->boolean('is_quarantined')->default(false);
            $table->unsignedBigInteger('moderator_id')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['reporter_id']);
            $table->index(['reported_user_id']);
            $table->index(['reportable_type', 'reportable_id']);
            $table->index(['status', 'priority_score']);
            $table->index(['created_at']);
            $table->index(['moderator_id']);
            $table->index(['category_id']);

            // Foreign key constraints with cascade
            $table->foreign('reporter_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');

            $table->foreign('reported_user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');

            $table->foreign('category_id')
                  ->references('id')
                  ->on('report_categories')
                  ->onDelete('restrict');

            $table->foreign('moderator_id')
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
        Schema::dropIfExists('reports');
    }
};