<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Alert>
 */
class AlertFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $attackTypes = ['DDoS', 'SQL Injection', 'XSS', 'Port Scan', 'Brute Force', 'Malware', 'Phishing', 'Man-in-the-Middle'];
        
        return [
            'network_log_id' => \App\Models\NetworkLog::factory(),
            'ml_model_id' => \App\Models\MLModel::factory(),
            'attack_type' => $this->faker->randomElement($attackTypes),
            'severity' => $this->faker->randomElement(['low', 'medium', 'high', 'critical']),
            'detected_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'description' => $this->faker->sentence(10),
        ];
    }
}
