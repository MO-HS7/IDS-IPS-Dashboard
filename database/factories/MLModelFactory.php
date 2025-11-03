<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MLModel>
 */
class MLModelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $modelNames = ['Random Forest IDS', 'Neural Network IDS', 'SVM Classifier', 'Decision Tree IDS', 'Naive Bayes IDS'];
        $modelTypes = ['Random Forest', 'Neural Network', 'Support Vector Machine', 'Decision Tree', 'Naive Bayes'];
        
        return [
            'name' => $this->faker->randomElement($modelNames),
            'description' => $this->faker->sentence(8) . ' trained on cybersecurity datasets.',
            'file_path' => 'models/' . strtolower(str_replace(' ', '_', $this->faker->randomElement($modelTypes))) . '_' . $this->faker->uuid() . '.pkl',
            'trained_at' => $this->faker->dateTimeBetween('-90 days', '-1 day'),
        ];
    }
}
