<?php

use App\Models\Course;
use App\Models\User;
use App\Services\EnrollmentService;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

beforeEach(function () {
    $this->service = new EnrollmentService();
    $this->actor = User::factory()->admin()->create();
});

it('enrolls a student in a course', function () {
    $course = Course::factory()->create();
    $student = User::factory()->student()->create();

    $this->service->enrollStudent($course, $student, $this->actor);

    expect($course->students()->where('user_id', $student->id)->exists())->toBeTrue();
});

it('rejects enrolling a student who is already enrolled', function () {
    $course = Course::factory()->create();
    $student = User::factory()->student()->create();
    $course->students()->attach($student->id);

    expect(fn () => $this->service->enrollStudent($course, $student, $this->actor))
        ->toThrow(ConflictHttpException::class, 'Student is already enrolled in this course.');
});

it('converts a DB-level unique constraint race into a clean 409 conflict', function () {
    $student = User::factory()->student()->create();
    $course = Course::factory()->create();

    // Simulate two concurrent requests both passing the pre-check, then
    // racing on the actual insert: the pivot's own unique constraint is
    // what really prevents the duplicate, so we force `attach()` to throw
    // the same QueryException the database would raise in that scenario.
    $courseMock = Mockery::mock($course)->makePartial();
    $studentsRelation = Mockery::mock(BelongsToMany::class);
    $studentsRelation->shouldReceive('where->exists')->andReturn(false);
    $studentsRelation->shouldReceive('attach')->andThrow(new QueryException(
        'sqlite',
        'insert into course_user',
        [],
        new PDOException('Integrity constraint violation', 23000)
    ));
    $courseMock->shouldReceive('students')->andReturn($studentsRelation);

    expect(fn () => $this->service->enrollStudent($courseMock, $student, $this->actor))
        ->toThrow(ConflictHttpException::class, 'Student is already enrolled in this course.');
});

it('lets an unrelated database error propagate instead of being swallowed as a conflict', function () {
    $student = User::factory()->student()->create();
    $course = Course::factory()->create();

    $courseMock = Mockery::mock($course)->makePartial();
    $studentsRelation = Mockery::mock(BelongsToMany::class);
    $studentsRelation->shouldReceive('where->exists')->andReturn(false);
    $studentsRelation->shouldReceive('attach')->andThrow(new QueryException(
        'sqlite',
        'insert into course_user',
        [],
        new PDOException('Connection lost', '08006')
    ));
    $courseMock->shouldReceive('students')->andReturn($studentsRelation);

    expect(fn () => $this->service->enrollStudent($courseMock, $student, $this->actor))
        ->toThrow(QueryException::class);
});

it('removes an enrolled student from a course', function () {
    $course = Course::factory()->create();
    $student = User::factory()->student()->create();
    $course->students()->attach($student->id);

    $this->service->removeStudent($course, $student, $this->actor);

    expect($course->students()->where('user_id', $student->id)->exists())->toBeFalse();
});

it('is idempotent when removing a student who was never enrolled', function () {
    $course = Course::factory()->create();
    $student = User::factory()->student()->create();

    $this->service->removeStudent($course, $student, $this->actor);

    expect($course->students()->where('user_id', $student->id)->exists())->toBeFalse();
});
