<?php

namespace Database\Seeders;

use App\Models\Assignment;
use App\Models\Course;
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

        [$studentB, $studentC, $studentD] = User::factory()->student()->count(3)->create();

        $vueCourse = Course::factory()->create([
            'instructor_id' => $instructor->id,
            'name' => 'Advanced Vue.js and Laravel',
            'description' => 'Build full-stack single-page applications with Vue 3 and a Laravel REST API.',
            'status' => 'active',
            'start_date' => now()->subDays(10)->toDateString(),
            'end_date' => now()->addDays(50)->toDateString(),
        ]);

        $dbCourse = Course::factory()->create([
            'instructor_id' => $instructor->id,
            'name' => 'Introduction to Database Design',
            'description' => 'Relational modeling, normalization, indexing, and query optimization.',
            'status' => 'active',
            'start_date' => now()->subDays(3)->toDateString(),
            'end_date' => now()->addDays(60)->toDateString(),
        ]);

        $testingCourse = Course::factory()->create([
            'instructor_id' => $instructor->id,
            'name' => 'Foundations of Software Testing',
            'description' => 'Unit, integration, and end-to-end testing practices.',
            'status' => 'archived',
            'start_date' => now()->subDays(120)->toDateString(),
            'end_date' => now()->subDays(30)->toDateString(),
        ]);

        // The documented demo student is enrolled everywhere, so logging in
        // as student@example.com immediately shows active and archived
        // courses with published assignments to submit.
        $vueCourse->students()->attach([$student->id, $studentB->id, $studentC->id]);
        $dbCourse->students()->attach([$student->id, $studentC->id, $studentD->id]);
        $testingCourse->students()->attach([$student->id, $studentB->id]);

        foreach ([$vueCourse, $dbCourse, $testingCourse] as $course) {
            Assignment::factory()->create([
                'course_id' => $course->id,
                'title' => 'Build a Fullstack App',
                'status' => 'published',
                'max_score' => 100,
            ]);

            Assignment::factory()->create([
                'course_id' => $course->id,
                'title' => 'API Authentication Lab',
                'status' => 'published',
                'max_score' => 50,
            ]);

            Assignment::factory()->create([
                'course_id' => $course->id,
                'title' => 'Final Project Proposal',
                'status' => 'draft',
                'max_score' => 100,
            ]);
        }
    }
}
