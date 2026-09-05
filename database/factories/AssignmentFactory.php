<?php

namespace Database\Factories;

use App\Models\Assignment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Assignment>
 */
class AssignmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'course_id' => \App\Models\Course::factory(),
            'title' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'due_date' => now()->addDays(14)->toISOString(),
            'max_score' => 100,
            'status' => \App\Enums\AssignmentStatus::Draft->value,
        ];
    }
}
