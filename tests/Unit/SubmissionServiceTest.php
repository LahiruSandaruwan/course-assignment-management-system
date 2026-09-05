<?php

use App\Enums\SubmissionStatus;
use App\Models\Assignment;
use App\Models\Submission;
use App\Models\User;
use App\Services\SubmissionService;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

beforeEach(function () {
    $this->service = new SubmissionService();
});

it('creates a new submission when none exists yet', function () {
    $assignment = Assignment::factory()->create();
    $student = User::factory()->student()->create();

    $submission = $this->service->submit($assignment, $student, 'My first answer');

    expect($submission->exists)->toBeTrue()
        ->and($submission->submission_text)->toBe('My first answer')
        ->and($submission->status)->toBe(SubmissionStatus::Submitted);

    expect(Submission::where('assignment_id', $assignment->id)
        ->where('student_id', $student->id)
        ->count())->toBe(1);
});

it('updates the existing row on resubmission instead of creating a duplicate', function () {
    $assignment = Assignment::factory()->create();
    $student = User::factory()->student()->create();
    $existing = Submission::factory()->create([
        'assignment_id' => $assignment->id,
        'student_id' => $student->id,
        'submission_text' => 'First try',
        'status' => SubmissionStatus::Submitted,
    ]);

    $submission = $this->service->submit($assignment, $student, 'Second try');

    expect($submission->id)->toBe($existing->id)
        ->and($submission->submission_text)->toBe('Second try');

    expect(Submission::where('assignment_id', $assignment->id)
        ->where('student_id', $student->id)
        ->count())->toBe(1);
});

it('blocks resubmission once the submission has already been graded', function () {
    $assignment = Assignment::factory()->create();
    $student = User::factory()->student()->create();
    Submission::factory()->create([
        'assignment_id' => $assignment->id,
        'student_id' => $student->id,
        'status' => SubmissionStatus::Graded,
        'score' => 90,
    ]);

    expect(fn () => $this->service->submit($assignment, $student, 'Sneaky edit'))
        ->toThrow(UnprocessableEntityHttpException::class, 'Cannot resubmit an assignment that has already been graded.');
});

it('resolves a concurrent insert race by updating the row the other request created', function () {
    $assignment = Assignment::factory()->create();
    $student = User::factory()->student()->create();

    // This service instance's own "does it exist?" check runs first and
    // finds nothing. Only when it tries to insert do we simulate the
    // other, concurrent request winning the race: its row lands in the
    // database right as our insert fires and collides with the unique
    // constraint on (assignment_id, student_id).
    $assignmentMock = Mockery::mock($assignment)->makePartial();
    $submissionsRelation = Mockery::mock(HasMany::class);
    $submissionsRelation->shouldReceive('create')->andReturnUsing(function () use ($assignment, $student) {
        Submission::factory()->create([
            'assignment_id' => $assignment->id,
            'student_id' => $student->id,
            'submission_text' => 'Winner of the race',
            'status' => SubmissionStatus::Submitted,
        ]);

        throw new QueryException(
            'sqlite',
            'insert into submissions',
            [],
            new PDOException('Integrity constraint violation', 23000)
        );
    });
    $assignmentMock->shouldReceive('submissions')->andReturn($submissionsRelation);

    $result = $this->service->submit($assignmentMock, $student, 'My late submission');

    expect($result->submission_text)->toBe('My late submission');

    expect(Submission::where('assignment_id', $assignment->id)
        ->where('student_id', $student->id)
        ->count())->toBe(1);
});

it('blocks the raced resubmission when the row it collided with was already graded', function () {
    $assignment = Assignment::factory()->create();
    $student = User::factory()->student()->create();

    $assignmentMock = Mockery::mock($assignment)->makePartial();
    $submissionsRelation = Mockery::mock(HasMany::class);
    $submissionsRelation->shouldReceive('create')->andReturnUsing(function () use ($assignment, $student) {
        Submission::factory()->create([
            'assignment_id' => $assignment->id,
            'student_id' => $student->id,
            'status' => SubmissionStatus::Graded,
            'score' => 70,
        ]);

        throw new QueryException(
            'sqlite',
            'insert into submissions',
            [],
            new PDOException('Integrity constraint violation', 23000)
        );
    });
    $assignmentMock->shouldReceive('submissions')->andReturn($submissionsRelation);

    expect(fn () => $this->service->submit($assignmentMock, $student, 'Too late'))
        ->toThrow(UnprocessableEntityHttpException::class, 'Cannot resubmit an assignment that has already been graded.');
});

it('lets an unrelated database error propagate instead of being swallowed', function () {
    $assignment = Assignment::factory()->create();
    $student = User::factory()->student()->create();

    $assignmentMock = Mockery::mock($assignment)->makePartial();
    $submissionsRelation = Mockery::mock(HasMany::class);
    $submissionsRelation->shouldReceive('create')->andThrow(new QueryException(
        'sqlite',
        'insert into submissions',
        [],
        new PDOException('Connection lost', '08006')
    ));
    $assignmentMock->shouldReceive('submissions')->andReturn($submissionsRelation);

    expect(fn () => $this->service->submit($assignmentMock, $student, 'Doomed submission'))
        ->toThrow(QueryException::class);
});
