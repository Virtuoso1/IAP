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
        Schema::create('report_evidence', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('report_id');
            $table->enum('evidence_type', ['screenshot', 'message_excerpt', 'file_upload', 'context_data']);
            $table->string('file_path', 1024)->nullable();
            $table->char('file_hash', 64)->nullable(); // SHA-256 hash
            $table->json('metadata')->nullable();
            $table->text('content')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['report_id']);
            $table->index(['evidence_type']);
            $table->index(['file_hash']);

            // Foreign key constraint
            $table->foreign('report_id')
                  ->references('id')
                  ->on('reports')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_evidence');
    }
};