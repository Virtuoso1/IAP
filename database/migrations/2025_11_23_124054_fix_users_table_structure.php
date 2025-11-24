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
        Schema::table('users', function (Blueprint $table) {
            // Fix the role enum to include all required values
            $table->enum('role', ['moderator', 'seeker', 'helper', 'hybrid', 'member'])->default('member')->change();
            
            // Make sure username field is properly set up
            if (!Schema::hasColumn('users', 'username')) {
                $table->string('username')->unique()->after('password');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Revert changes if needed
            $table->enum('role', ['moderator', 'seeker', 'helper', 'hybrid', 'member'])->default('member')->change();
        });
    }
};
