<?php

use App\Models\User;
use App\Models\Course;
use App\Models\Assignment;
use App\Models\Submission;
use App\Enums\AssignmentStatus;
use App\Events\SubmissionGraded;
use Illuminate\Support\Facades\Event;

it('allows instructor to grade a submission in their own course', function () {
    Event::fake();

    $instructor = User::factory()->instructor()->create();
    $student = User::factory()->student()->create();
    $course = Course::factory()->create([
        'instructor_id' => $instructor->id,
    ]);
    $course->students()->attach($student->id);
    $assignment = Assignment::factory()->create([
        'course_id' => $course->id,
        'status' => AssignmentStatus::Published,
        'max_score' => 100,
    ]);
    $submission = Submission::factory()->create([
        'assignment_id' => $assignment->id,
        'student_id' => $student->id,
    ]);

    $response = $this->actingAs($instructor)->putJson("/api/submissions/{$submission->id}/grade", [
        'score' => 95,
        'instructor_feedback' => 'Excellent work!',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.score', 95)
        ->assertJsonPath('data.status', \App\Enums\SubmissionStatus::Graded->value);

    Event::assertDispatched(SubmissionGraded::class, function ($event) use ($submission) {
        return $event->submission->id === $submission->id;
    });
});

it('prevents instructor from grading a submission in another instructors course', function () {
    $instructorA = User::factory()->instructor()->create();
    $instructorB = User::factory()->instructor()->create();
    $student = User::factory()->student()->create();
    
    $courseA = Course::factory()->create([
        'instructor_id' => $instructorA->id,
    ]);
    $courseA->students()->attach($student->id);
    $assignmentA = Assignment::factory()->create([
        'course_id' => $courseA->id,
        'status' => AssignmentStatus::Published,
    ]);
    $submission = Submission::factory()->create([
        'assignment_id' => $assignmentA->id,
        'student_id' => $student->id,
    ]);

    $response = $this->actingAs($instructorB)->putJson("/api/submissions/{$submission->id}/grade", [
        'score' => 80,
    ]);

    $response->assertStatus(403);
});

it('prevents student from grading any submission', function () {
    $instructor = User::factory()->instructor()->create();
    $student = User::factory()->student()->create();
    
    $course = Course::factory()->create([
        'instructor_id' => $instructor->id,
    ]);
    $course->students()->attach($student->id);
    $assignment = Assignment::factory()->create([
        'course_id' => $course->id,
        'status' => AssignmentStatus::Published,
    ]);
    $submission = Submission::factory()->create([
        'assignment_id' => $assignment->id,
        'student_id' => $student->id,
    ]);

    $response = $this->actingAs($student)->putJson("/api/submissions/{$submission->id}/grade", [
        'score' => 100,
    ]);

    $response->assertStatus(403);
});

it('rejects score above max_score', function () {
    $instructor = User::factory()->instructor()->create();
    $student = User::factory()->student()->create();
    $course = Course::factory()->create([
        'instructor_id' => $instructor->id,
    ]);
    $course->students()->attach($student->id);
    $assignment = Assignment::factory()->create([
        'course_id' => $course->id,
        'status' => AssignmentStatus::Published,
        'max_score' => 50,
    ]);
    $submission = Submission::factory()->create([
        'assignment_id' => $assignment->id,
        'student_id' => $student->id,
    ]);

    $response = $this->actingAs($instructor)->putJson("/api/submissions/{$submission->id}/grade", [
        'score' => 51,
    ]);

    $response->assertStatus(422);
});

it('rejects negative score', function () {
    $instructor = User::factory()->instructor()->create();
    $student = User::factory()->student()->create();
    $course = Course::factory()->create([
        'instructor_id' => $instructor->id,
    ]);
    $course->students()->attach($student->id);
    $assignment = Assignment::factory()->create([
        'course_id' => $course->id,
        'status' => AssignmentStatus::Published,
        'max_score' => 100,
    ]);
    $submission = Submission::factory()->create([
        'assignment_id' => $assignment->id,
        'student_id' => $student->id,
    ]);

    $response = $this->actingAs($instructor)->putJson("/api/submissions/{$submission->id}/grade", [
        'score' => -5,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['score']);
});

it('accepts a score of exactly 0', function () {
    $instructor = User::factory()->instructor()->create();
    $student = User::factory()->student()->create();
    $course = Course::factory()->create([
        'instructor_id' => $instructor->id,
    ]);
    $course->students()->attach($student->id);
    $assignment = Assignment::factory()->create([
        'course_id' => $course->id,
        'status' => AssignmentStatus::Published,
        'max_score' => 100,
    ]);
    $submission = Submission::factory()->create([
        'assignment_id' => $assignment->id,
        'student_id' => $student->id,
    ]);

    $response = $this->actingAs($instructor)->putJson("/api/submissions/{$submission->id}/grade", [
        'score' => 0,
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.score', 0);
});
