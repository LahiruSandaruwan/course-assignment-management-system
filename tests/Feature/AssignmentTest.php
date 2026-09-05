<?php

use App\Models\User;
use App\Models\Course;
use App\Models\Assignment;
use App\Enums\AssignmentStatus;

it('allows instructor to create assignment in their own course', function () {
    $instructor = User::factory()->instructor()->create();
    $course = Course::factory()->create([
        'instructor_id' => $instructor->id,
    ]);

    $response = $this->actingAs($instructor)->postJson("/api/courses/{$course->id}/assignments", [
        'title' => 'Test Assignment',
        'due_date' => now()->addDays(7)->toISOString(),
        'max_score' => 100,
        'status' => 'draft',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.title', 'Test Assignment');
});

it('prevents instructor from creating assignment in someone elses course', function () {
    $instructor1 = User::factory()->instructor()->create();
    $instructor2 = User::factory()->instructor()->create();
    $course = Course::factory()->create([
        'instructor_id' => $instructor1->id,
    ]);

    $response = $this->actingAs($instructor2)->postJson("/api/courses/{$course->id}/assignments", [
        'title' => 'Test Assignment',
        'due_date' => now()->addDays(7)->toISOString(),
        'max_score' => 100,
    ]);

    $response->assertStatus(403);
});

it('allows instructor to update assignment in their own course', function () {
    $instructor = User::factory()->instructor()->create();
    $course = Course::factory()->create([
        'instructor_id' => $instructor->id,
    ]);
    $assignment = Assignment::factory()->create([
        'course_id' => $course->id,
        'title' => 'Old Title',
    ]);

    $response = $this->actingAs($instructor)->putJson("/api/assignments/{$assignment->id}", [
        'title' => 'Updated Title',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.title', 'Updated Title');
});

it('allows enrolled student to view an assignment', function () {
    $student = User::factory()->student()->create();
    $course = Course::factory()->create();
    $course->students()->attach($student->id);
    
    $assignment = Assignment::factory()->create([
        'course_id' => $course->id,
    ]);

    $response = $this->actingAs($student)->getJson("/api/assignments/{$assignment->id}");

    $response->assertStatus(200)
        ->assertJsonPath('data.id', $assignment->id);
});

it('prevents non-enrolled student from viewing an assignment', function () {
    $student = User::factory()->student()->create();
    $course = Course::factory()->create();
    // student not enrolled
    
    $assignment = Assignment::factory()->create([
        'course_id' => $course->id,
    ]);

    $response = $this->actingAs($student)->getJson("/api/assignments/{$assignment->id}");

    $response->assertStatus(403);
});

it('prevents non-enrolled student from listing assignments', function () {
    $student = User::factory()->student()->create();
    $course = Course::factory()->create();
    // student not enrolled

    $response = $this->actingAs($student)->getJson("/api/courses/{$course->id}/assignments");

    $response->assertStatus(403);
});

it('returns 422 for missing title, invalid due date, and max score < 1', function () {
    $instructor = User::factory()->instructor()->create();
    $course = Course::factory()->create([
        'instructor_id' => $instructor->id,
    ]);

    $response = $this->actingAs($instructor)->postJson("/api/courses/{$course->id}/assignments", [
        // missing title
        'due_date' => 'not-a-date',
        'max_score' => 0,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['title', 'due_date', 'max_score']);
});
