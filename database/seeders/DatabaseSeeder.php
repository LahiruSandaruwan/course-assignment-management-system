<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::factory()->admin()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
        ]);

        $instructor = User::factory()->instructor()->create([
            'name' => 'Instructor User',
            'email' => 'instructor@example.com',
        ]);

        $student = User::factory()->student()->create([
            'name' => 'Student User',
            'email' => 'student@example.com',
        ]);

        // Create a dummy course for the instructor
        $course = \App\Models\Course::factory()->create([
            'instructor_id' => $instructor->id,
            'name' => 'Advanced Vue.js and Laravel',
            'status' => 'active',
        ]);

        // Enroll the student in the course
        $course->students()->attach($student->id);

        // Create an assignment for the course
        $assignment = \App\Models\Assignment::factory()->create([
            'course_id' => $course->id,
            'title' => 'Build a Fullstack App',
            'status' => 'published',
            'max_score' => 100,
        ]);
    }
}
