<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AgentSeeder extends Seeder
{
    public function run(): void
    {
        $agent = User::firstOrCreate(
            ['email' => 'agent@allleads.local'],
            [
                'name' => 'Demo Agent',
                'password' => Hash::make('password'),
            ]
        );

        $agent->syncRoles('agent');
    }
}
