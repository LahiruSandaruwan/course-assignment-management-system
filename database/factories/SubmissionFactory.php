<?php

namespace Database\Factories;

use App\Models\Submission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Submission>
 */
class SubmissionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'assignment_id' => \App\Models\Assignment::factory(),
            'student_id' => \App\Models\User::factory()->student(),
            'submission_text' => fake()->paragraph(),
            'submitted_at' => now()->toISOString(),
            'status' => \App\Enums\SubmissionStatus::Submitted->value,
            'score' => null,
            'instructor_feedback' => null,
        ];
    }
}
