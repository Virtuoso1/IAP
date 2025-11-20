<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Create a moderator
        User::create([
            'email' => 'moderator@safespace.com',
            'password' => Hash::make('password'),
            'username' => 'mod_admin',
            'role' => 'moderator',
            'status' => 'active',
        ]);

        // Create a helper
        User::create([
            'email' => 'helper@safespace.com',
            'password' => Hash::make('password'),
            'username' => 'helpful_peer',
            'role' => 'helper',
            'is_available' => true,
            'status' => 'active',
        ]);

        // Create a seeker
        User::create([
            'email' => 'seeker@safespace.com',
            'password' => Hash::make('password'),
            'username' => 'need_support',
            'role' => 'seeker',
            'status' => 'active',
        ]);

        // Create a hybrid user
        User::create([
            'email' => 'hybrid@safespace.com',
            'password' => Hash::make('password'),
            'username' => 'peer_supporter',
            'role' => 'hybrid',
            'is_available' => true,
            'status' => 'active',
        ]);
    }
}