<?php

use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Facades\DB;

it('measures n+1 queries before optimization', function () {
    // Generate 5 courses with some relationships
    $instructor = User::factory()->instructor()->create();
    for ($i = 0; $i < 5; $i++) {
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $course->students()->attach(User::factory()->student()->count(2)->create());
        \App\Models\Assignment::factory()->count(2)->create(['course_id' => $course->id]);
    }

    DB::enableQueryLog();

    $courses = Course::all();
    foreach ($courses as $course) {
        $name = $course->instructor->name;
        $studentCount = $course->students->count();
        $assignmentCount = $course->assignments->count();
    }

    $queries = DB::getQueryLog();
    // 1 query for courses
    // + 5 queries for instructor
    // + 5 queries for students
    // + 5 queries for assignments
    // = 16 queries
    $this->assertCount(16, $queries);

    DB::disableQueryLog();
});

it('measures queries after optimization', function () {
    // Re-use the existing data if DB isn't reset, but Pest resets it for each test
    $instructor = User::factory()->instructor()->create();
    for ($i = 0; $i < 5; $i++) {
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $course->students()->attach(User::factory()->student()->count(2)->create());
        \App\Models\Assignment::factory()->count(2)->create(['course_id' => $course->id]);
    }

    DB::flushQueryLog();
    DB::enableQueryLog();

    // The optimized version matching our fix
    $courses = Course::with('instructor:id,name')->withCount(['students', 'assignments'])->get();
    foreach ($courses as $course) {
        $name = $course->instructor->name;
        $studentCount = $course->students_count;
        $assignmentCount = $course->assignments_count;
    }

    $queries = DB::getQueryLog();
    // 1 query for courses + 2 subqueries for counts (withCount runs as subqueries in the main select, or joined)
    // Actually, withCount runs as subqueries in Laravel, so it's all in 1 query!
    // And 1 query for eager-loading instructor (using IN)
    // = 2 queries total!
    $this->assertCount(2, $queries);
    
    DB::disableQueryLog();
});
