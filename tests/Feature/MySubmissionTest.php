<?php

use App\Models\User;
use App\Models\Course;
use App\Models\Assignment;
use App\Models\Submission;

it('returns 404 for student without a submission', function () {
    $student = User::factory()->student()->create();
    $course = Course::factory()->create();
    $course->students()->attach($student);
    
    $assignment = Assignment::factory()->create([
        'course_id' => $course->id,
        'status' => 'published'
    ]);

    $response = $this->actingAs($student, 'sanctum')->getJson("/api/assignments/{$assignment->id}/submissions/mine");

    $response->assertStatus(404);
});

it('returns the submission for the authenticated student', function () {
    $student = User::factory()->student()->create();
    $course = Course::factory()->create();
    $course->students()->attach($student);
    
    $assignment = Assignment::factory()->create([
        'course_id' => $course->id,
        'status' => 'published'
    ]);

    $submission = Submission::factory()->create([
        'assignment_id' => $assignment->id,
        'student_id' => $student->id,
        'submission_text' => 'My answer',
        'status' => 'submitted'
    ]);

    $response = $this->actingAs($student, 'sanctum')->getJson("/api/assignments/{$assignment->id}/submissions/mine");

    $response->assertStatus(200)
             ->assertJsonPath('data.submission_text', 'My answer')
             ->assertJsonPath('data.student_id', $student->id);
});

it('returns 403 if user is not enrolled in the course', function () {
    $student = User::factory()->student()->create();
    $course = Course::factory()->create();
    
    $assignment = Assignment::factory()->create([
        'course_id' => $course->id,
        'status' => 'published'
    ]);

    $response = $this->actingAs($student, 'sanctum')->getJson("/api/assignments/{$assignment->id}/submissions/mine");

    $response->assertStatus(403);
});
