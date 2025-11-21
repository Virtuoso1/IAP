<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Group;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class GroupDemoSeeder extends Seeder
{
    public function run()
    {
        // Create a demo user
        $user = User::firstOrCreate([
            'email' => 'demo2@example.com',
        ], [
            'username' => 'demo2',
            'password' => Hash::make('password'),
            'role' => 'member',
            'status' => 'active',
        ]);

        // Create a demo group
        $group = Group::firstOrCreate([
            'name' => 'Demo Group',
        ], [
            'description' => 'A group for demo purposes.',
            'owner_id' => $user->id,
        ]);

        // Attach user to group as active member
        DB::table('group_user')->updateOrInsert([
            'group_id' => $group->id,
            'user_id' => $user->id,
        ], [
            'role' => 'member',
            'status' => 'active',
            'joined_at' => now(),
        ]);
    }
}
