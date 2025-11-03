<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        DB::table('users')->insert([
            [
                'name' => 'Admin User',
                'email' => 'admin@ids.com',
                'password' => Hash::make('password'),
                'role' => 'Admin',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Analyst User',
                'email' => 'analyst@ids.com',
                'password' => Hash::make('password'),
                'role' => 'Analyst',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Viewer User',
                'email' => 'viewer@ids.com',
                'password' => Hash::make('password'),
                'role' => 'Viewer',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}
