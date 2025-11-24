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
        Schema::create('user_restrictions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->enum('restriction_type', ['posting', 'messaging', 'group_creation', 'profile_view', 'full_access', 'reporting']);
            $table->enum('severity', ['warning', 'temporary', 'permanent']);
            $table->text('reason');
            $table->timestamp('starts_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('moderator_id');
            $table->unsignedBigInteger('warning_id')->nullable();
            $table->string('ip_address', 45)->nullable(); // IPv6 compatible
            $table->string('device_fingerprint', 255)->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['user_id']);
            $table->index(['restriction_type', 'severity']);
            $table->index(['is_active', 'starts_at', 'ends_at']);
            $table->index(['ip_address', 'device_fingerprint']);

            // Foreign key constraints
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');

            $table->foreign('moderator_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');

            $table->foreign('warning_id')
                  ->references('id')
                  ->on('warnings')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_restrictions');
    }
};