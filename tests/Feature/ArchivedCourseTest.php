<?php

use App\Models\User;
use App\Models\Course;
use App\Models\Assignment;
use App\Enums\CourseStatus;
use App\Enums\AssignmentStatus;

it('allows enrolled student to view an archived course', function () {
    $student = User::factory()->student()->create();
    $course = Course::factory()->create([
        'status' => CourseStatus::Archived->value,
    ]);
    $course->students()->attach($student);

    $response = $this->actingAs($student)->getJson("/api/courses/{$course->id}");

    $response->assertStatus(200)
             ->assertJsonPath('data.id', $course->id);
});

it('prevents non-enrolled student from viewing an archived course', function () {
    $student = User::factory()->student()->create();
    $course = Course::factory()->create([
        'status' => CourseStatus::Archived->value,
    ]);

    $response = $this->actingAs($student)->getJson("/api/courses/{$course->id}");

    $response->assertStatus(403);
});

it('prevents submitting an assignment to an archived course', function () {
    $student = User::factory()->student()->create();
    $course = Course::factory()->create([
        'status' => CourseStatus::Archived->value,
    ]);
    $course->students()->attach($student);

    $assignment = Assignment::factory()->create([
        'course_id' => $course->id,
        'status' => AssignmentStatus::Published->value,
    ]);

    $response = $this->actingAs($student)->postJson("/api/assignments/{$assignment->id}/submissions", [
        'submission_text' => 'This should be blocked',
    ]);

    $response->assertStatus(403);
});

it('allows admin to archive a course', function () {
    $admin = User::factory()->admin()->create();
    $course = Course::factory()->create([
        'status' => CourseStatus::Active->value,
    ]);

    $response = $this->actingAs($admin)->putJson("/api/courses/{$course->id}", [
        'name' => $course->name,
        'status' => CourseStatus::Archived->value,
    ]);

    $response->assertStatus(200)
             ->assertJsonPath('data.status', 'archived');
});

it('allows instructor to archive their own course', function () {
    $instructor = User::factory()->instructor()->create();
    $course = Course::factory()->create([
        'instructor_id' => $instructor->id,
        'status' => CourseStatus::Active->value,
    ]);

    $response = $this->actingAs($instructor)->putJson("/api/courses/{$course->id}", [
        'name' => $course->name,
        'status' => CourseStatus::Archived->value,
    ]);

    $response->assertStatus(200)
             ->assertJsonPath('data.status', 'archived');
});
