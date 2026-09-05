<?php

use App\Models\User;
use App\Models\Course;
use App\Models\Assignment;
use App\Models\Submission;
use App\Enums\AssignmentStatus;

it('prevents a random student from viewing another course', function () {
    $owner = User::factory()->instructor()->create();
    $course = Course::factory()->create([
        'instructor_id' => $owner->id
    ]);
    
    $randomStudent = User::factory()->student()->create();

    $response = $this->actingAs($randomStudent)->getJson("/api/courses/{$course->id}");

    $response->assertStatus(403);
});

it('prevents a random instructor from viewing another course', function () {
    $owner = User::factory()->instructor()->create();
    $course = Course::factory()->create([
        'instructor_id' => $owner->id
    ]);
    
    $randomInstructor = User::factory()->instructor()->create();

    $response = $this->actingAs($randomInstructor)->getJson("/api/courses/{$course->id}");

    $response->assertStatus(403);
});

it('prevents a random student from viewing an assignment of a non-enrolled course', function () {
    $owner = User::factory()->instructor()->create();
    $course = Course::factory()->create([
        'instructor_id' => $owner->id
    ]);
    $assignment = Assignment::factory()->create([
        'course_id' => $course->id,
        'status' => AssignmentStatus::Published->value,
    ]);
    
    $randomStudent = User::factory()->student()->create();

    $response = $this->actingAs($randomStudent)->getJson("/api/assignments/{$assignment->id}");

    $response->assertStatus(403);
});

it('prevents a random instructor from viewing an assignment of another course', function () {
    $owner = User::factory()->instructor()->create();
    $course = Course::factory()->create([
        'instructor_id' => $owner->id
    ]);
    $assignment = Assignment::factory()->create([
        'course_id' => $course->id,
        'status' => AssignmentStatus::Published->value,
    ]);
    
    $randomInstructor = User::factory()->instructor()->create();

    $response = $this->actingAs($randomInstructor)->getJson("/api/assignments/{$assignment->id}");

    $response->assertStatus(403);
});

it('prevents a random student from viewing another students submission', function () {
    $owner = User::factory()->instructor()->create();
    $course = Course::factory()->create([
        'instructor_id' => $owner->id
    ]);
    $assignment = Assignment::factory()->create([
        'course_id' => $course->id,
        'status' => AssignmentStatus::Published->value,
    ]);
    
    $studentA = User::factory()->student()->create();
    $course->students()->attach($studentA);
    $submission = Submission::factory()->create([
        'assignment_id' => $assignment->id,
        'student_id' => $studentA->id,
    ]);
    
    $studentB = User::factory()->student()->create();
    $course->students()->attach($studentB);

    $response = $this->actingAs($studentB)->getJson("/api/submissions/{$submission->id}");

    $response->assertStatus(403);
});
