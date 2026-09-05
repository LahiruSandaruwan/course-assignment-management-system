<?php

use App\Enums\SubmissionStatus;
use App\Events\SubmissionGraded;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\Submission;
use App\Services\GradingService;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->service = new GradingService();
});

it('grades a submission, storing the score, feedback, and graded status', function () {
    $course = Course::factory()->create();
    $assignment = Assignment::factory()->create([
        'course_id' => $course->id,
        'max_score' => 100,
    ]);
    $submission = Submission::factory()->create([
        'assignment_id' => $assignment->id,
    ]);

    $graded = $this->service->grade($submission, 85, 'Solid work');

    expect($graded->score)->toBe(85)
        ->and($graded->instructor_feedback)->toBe('Solid work')
        ->and($graded->status)->toBe(SubmissionStatus::Graded)
        ->and($submission->fresh()->status)->toBe(SubmissionStatus::Graded);
});

it('accepts a score of exactly 0', function () {
    $assignment = Assignment::factory()->create(['max_score' => 100]);
    $submission = Submission::factory()->create(['assignment_id' => $assignment->id]);

    $graded = $this->service->grade($submission, 0, null);

    expect($graded->score)->toBe(0);
});

it('accepts a score exactly equal to the assignment max score', function () {
    $assignment = Assignment::factory()->create(['max_score' => 50]);
    $submission = Submission::factory()->create(['assignment_id' => $assignment->id]);

    $graded = $this->service->grade($submission, 50, null);

    expect($graded->score)->toBe(50);
});

it('rejects a score above the assignment max score and leaves the submission ungraded', function () {
    $assignment = Assignment::factory()->create(['max_score' => 50]);
    $submission = Submission::factory()->create(['assignment_id' => $assignment->id]);

    expect(fn () => $this->service->grade($submission, 51, null))
        ->toThrow(ValidationException::class);

    expect($submission->fresh()->status)->toBe(SubmissionStatus::Submitted)
        ->and($submission->fresh()->score)->toBeNull();
});

it('rejects a negative score', function () {
    $assignment = Assignment::factory()->create(['max_score' => 100]);
    $submission = Submission::factory()->create(['assignment_id' => $assignment->id]);

    expect(fn () => $this->service->grade($submission, -1, null))
        ->toThrow(ValidationException::class);
});

it('dispatches a SubmissionGraded event once grading succeeds', function () {
    Event::fake();

    $assignment = Assignment::factory()->create(['max_score' => 100]);
    $submission = Submission::factory()->create(['assignment_id' => $assignment->id]);

    $this->service->grade($submission, 90, 'Nice');

    Event::assertDispatched(SubmissionGraded::class, function ($event) use ($submission) {
        return $event->submission->id === $submission->id;
    });
});

it('does not dispatch a SubmissionGraded event when validation fails', function () {
    Event::fake();

    $assignment = Assignment::factory()->create(['max_score' => 10]);
    $submission = Submission::factory()->create(['assignment_id' => $assignment->id]);

    try {
        $this->service->grade($submission, 11, null);
    } catch (ValidationException) {
        // expected
    }

    Event::assertNotDispatched(SubmissionGraded::class);
});
