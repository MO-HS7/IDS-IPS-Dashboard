<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\NetworkLog>
 */
class NetworkLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'file_name' => $this->faker->word() . '_network_log.csv',
            'file_path' => 'network_logs/' . $this->faker->uuid() . '.csv',
            'upload_date' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'status' => $this->faker->randomElement(['pending', 'processing', 'processed', 'failed']),
        ];
    }
}
