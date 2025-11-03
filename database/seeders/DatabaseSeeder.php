<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\NetworkLog;
use App\Models\Alert;
use App\Models\MLModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@ids.com',
            'password' => Hash::make('password'),
            'role' => 'Admin',
        ]);

        // Create analyst users
        $analyst1 = User::factory()->create([
            'name' => 'Mohannad Analyst',
            'email' => 'analyst1@ids.com',
            'password' => Hash::make('password'),
            'role' => 'Analyst',
        ]);

        $analyst2 = User::factory()->create([
            'name' => 'Hassan Analyst',
            'email' => 'analyst2@ids.com',
            'password' => Hash::make('password'),
            'role' => 'Analyst',
        ]);

        // Create Viewer users
        $Viewer = User::factory()->create([
            'name' => 'Viewer User',
            'email' => 'Viewer@ids.com',
            'password' => Hash::make('password'),
            'role' => 'Viewer',
        ]);

        // Create additional users
        User::factory(5)->create();

        // Create ML models
        $mlModel1 = MLModel::factory()->create([
            'name' => 'Random Forest IDS',
            'description' => 'Random Forest model trained on NSL-KDD dataset',
            'file_path' => 'models/random_forest_ids.pkl',
            'trained_at' => now()->subDays(30),
        ]);

        $mlModel2 = MLModel::factory()->create([
            'name' => 'Neural Network IDS',
            'description' => 'Deep Neural Network model for intrusion detection',
            'file_path' => 'models/neural_network_ids.pkl',
            'trained_at' => now()->subDays(15),
        ]);

        // Create network logs
        NetworkLog::factory(20)->create();

        // Create alerts
        Alert::factory(50)->create();

        // Assign some alerts to analysts
        $alerts = Alert::all();
        foreach ($alerts as $alert) {
            if (rand(1, 3) === 1) { // 33% chance to assign
                $analyst = rand(1, 2) === 1 ? $analyst1 : $analyst2;
                $alert->users()->attach($analyst->id, ['assigned_at' => now()]);
            }
        }
    }
}
