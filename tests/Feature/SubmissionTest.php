<?php

use App\Models\User;
use App\Models\Course;
use App\Models\Assignment;
use App\Models\Submission;
use App\Enums\AssignmentStatus;

it('allows student to submit to an assignment in an enrolled course', function () {
    $student = User::factory()->student()->create();
    $course = Course::factory()->create();
    $course->students()->attach($student->id);
    $assignment = Assignment::factory()->create([
        'course_id' => $course->id,
        'status' => AssignmentStatus::Published,
    ]);

    $response = $this->actingAs($student)->postJson("/api/assignments/{$assignment->id}/submissions", [
        'submission_text' => 'My first submission',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.submission_text', 'My first submission');

    $this->assertDatabaseHas('submissions', [
        'assignment_id' => $assignment->id,
        'student_id' => $student->id,
        'submission_text' => 'My first submission',
    ]);
});

it('prevents student from submitting to an assignment in a course they are not enrolled in', function () {
    $student = User::factory()->student()->create();
    $course = Course::factory()->create();
    // Student not enrolled
    $assignment = Assignment::factory()->create([
        'course_id' => $course->id,
        'status' => AssignmentStatus::Published,
    ]);

    $response = $this->actingAs($student)->postJson("/api/assignments/{$assignment->id}/submissions", [
        'submission_text' => 'My sneaky submission',
    ]);

    $response->assertStatus(403);
});

it('prevents student from submitting a draft assignment', function () {
    $student = User::factory()->student()->create();
    $course = Course::factory()->create();
    $course->students()->attach($student->id);
    $assignment = Assignment::factory()->create([
        'course_id' => $course->id,
        'status' => AssignmentStatus::Draft,
    ]);

    $response = $this->actingAs($student)->postJson("/api/assignments/{$assignment->id}/submissions", [
        'submission_text' => 'I found a draft',
    ]);

    $response->assertStatus(403);
});

it('updates existing row on resubmission without duplicates', function () {
    $student = User::factory()->student()->create();
    $course = Course::factory()->create();
    $course->students()->attach($student->id);
    $assignment = Assignment::factory()->create([
        'course_id' => $course->id,
        'status' => AssignmentStatus::Published,
    ]);

    // Initial submission
    $this->actingAs($student)->postJson("/api/assignments/{$assignment->id}/submissions", [
        'submission_text' => 'First try',
    ]);

    // Resubmission
    $response = $this->actingAs($student)->postJson("/api/assignments/{$assignment->id}/submissions", [
        'submission_text' => 'Second try',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.submission_text', 'Second try');

    $this->assertDatabaseCount('submissions', 1);
    $this->assertDatabaseHas('submissions', [
        'submission_text' => 'Second try',
    ]);
});

it('prevents IDOR: student cannot fetch another students submission', function () {
    $studentA = User::factory()->student()->create();
    $studentB = User::factory()->student()->create();
    $course = Course::factory()->create();
    $course->students()->attach([$studentA->id, $studentB->id]);
    $assignment = Assignment::factory()->create([
        'course_id' => $course->id,
        'status' => AssignmentStatus::Published,
    ]);

    $submissionB = Submission::factory()->create([
        'assignment_id' => $assignment->id,
        'student_id' => $studentB->id,
    ]);

    $response = $this->actingAs($studentA)->getJson("/api/submissions/{$submissionB->id}");

    $response->assertStatus(403);
});

it('allows student to view their own submission', function () {
    $student = User::factory()->student()->create();
    $course = Course::factory()->create();
    $course->students()->attach($student->id);
    $assignment = Assignment::factory()->create([
        'course_id' => $course->id,
        'status' => AssignmentStatus::Published,
    ]);

    $submission = Submission::factory()->create([
        'assignment_id' => $assignment->id,
        'student_id' => $student->id,
    ]);

    $response = $this->actingAs($student)->getJson("/api/submissions/{$submission->id}");

    $response->assertStatus(200)
        ->assertJsonPath('data.id', $submission->id);
});

it('allows instructor to view submissions for their own course assignments', function () {
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

    // List view
    $responseList = $this->actingAs($instructor)->getJson("/api/assignments/{$assignment->id}/submissions");
    $responseList->assertStatus(200)->assertJsonPath('data.data.0.id', $submission->id);

    // Single view
    $responseSingle = $this->actingAs($instructor)->getJson("/api/submissions/{$submission->id}");
    $responseSingle->assertStatus(200)->assertJsonPath('data.id', $submission->id);
});

it('prevents instructor from viewing submissions for another instructors course', function () {
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

    // Instructor B attempts to access Instructor A's course submissions
    $responseList = $this->actingAs($instructorB)->getJson("/api/assignments/{$assignmentA->id}/submissions");
    $responseList->assertStatus(403);

    $responseSingle = $this->actingAs($instructorB)->getJson("/api/submissions/{$submission->id}");
    $responseSingle->assertStatus(403);
});

it('ignores malicious student_id in request body and uses authenticated user', function () {
    $hacker = User::factory()->student()->create();
    $victim = User::factory()->student()->create();
    
    $course = Course::factory()->create();
    $course->students()->attach([$hacker->id, $victim->id]);
    $assignment = Assignment::factory()->create([
        'course_id' => $course->id,
        'status' => AssignmentStatus::Published,
    ]);

    $this->actingAs($hacker)->postJson("/api/assignments/{$assignment->id}/submissions", [
        'submission_text' => 'Hacker submission',
        'student_id' => $victim->id, // Malicious payload
    ]);

    $this->assertDatabaseHas('submissions', [
        'submission_text' => 'Hacker submission',
        'student_id' => $hacker->id, // Attributed to the hacker, not victim
    ]);
    
    $this->assertDatabaseMissing('submissions', [
        'submission_text' => 'Hacker submission',
        'student_id' => $victim->id, // Victim was protected
    ]);
});
