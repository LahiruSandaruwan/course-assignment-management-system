<?php

use App\Enums\CourseStatus;
use App\Models\Course;
use App\Models\User;
use App\Services\CourseService;

beforeEach(function () {
    $this->service = new CourseService();
});

it('scopes the course list to only the instructors own courses', function () {
    $instructor = User::factory()->instructor()->create();
    $otherInstructor = User::factory()->instructor()->create();
    $ownCourse = Course::factory()->create(['instructor_id' => $instructor->id]);
    Course::factory()->create(['instructor_id' => $otherInstructor->id]);

    $results = $this->service->getCoursesForUser($instructor);

    expect($results->total())->toBe(1)
        ->and($results->first()->id)->toBe($ownCourse->id);
});

it('scopes the course list to only active courses for a student', function () {
    $student = User::factory()->student()->create();
    $active = Course::factory()->create(['status' => CourseStatus::Active]);
    $archived = Course::factory()->create(['status' => CourseStatus::Archived]);
    $active->students()->attach($student->id);
    $archived->students()->attach($student->id);

    $results = $this->service->getCoursesForUser($student);

    expect($results->total())->toBe(1)
        ->and($results->first()->id)->toBe($active->id);
});

it('excludes an active course the student is not enrolled in', function () {
    $student = User::factory()->student()->create();
    $enrolled = Course::factory()->create(['status' => CourseStatus::Active]);
    Course::factory()->create(['status' => CourseStatus::Active]); // not enrolled
    $enrolled->students()->attach($student->id);

    $results = $this->service->getCoursesForUser($student);

    expect($results->total())->toBe(1)
        ->and($results->first()->id)->toBe($enrolled->id);
});

it('returns every course, active or archived, for an admin', function () {
    $admin = User::factory()->admin()->create();
    Course::factory()->create(['status' => CourseStatus::Active]);
    Course::factory()->create(['status' => CourseStatus::Archived]);

    $results = $this->service->getCoursesForUser($admin);

    expect($results->total())->toBe(2);
});

it('eager loads the instructor and student/assignment counts to avoid N+1 queries', function () {
    $instructor = User::factory()->instructor()->create();
    Course::factory()->create(['instructor_id' => $instructor->id]);

    $course = $this->service->getCoursesForUser($instructor)->first();

    expect($course->relationLoaded('instructor'))->toBeTrue()
        ->and($course->students_count)->toBe(0)
        ->and($course->assignments_count)->toBe(0);
});

it('forces instructor_id to the acting instructor, ignoring any client-supplied value', function () {
    $instructor = User::factory()->instructor()->create();
    $someoneElse = User::factory()->instructor()->create();

    $course = $this->service->createCourse([
        'name' => 'Intro to Testing',
        'start_date' => now()->toDateString(),
        'end_date' => now()->addDays(30)->toDateString(),
        'instructor_id' => $someoneElse->id, // attempted spoof
    ], $instructor);

    expect($course->instructor_id)->toBe($instructor->id);
});

it('defaults instructor_id to the acting admin when none is supplied', function () {
    $admin = User::factory()->admin()->create();

    $course = $this->service->createCourse([
        'name' => 'Admin Created Course',
        'start_date' => now()->toDateString(),
        'end_date' => now()->addDays(30)->toDateString(),
    ], $admin);

    expect($course->instructor_id)->toBe($admin->id);
});

it('lets an admin assign an explicit instructor to a new course', function () {
    $admin = User::factory()->admin()->create();
    $instructor = User::factory()->instructor()->create();

    $course = $this->service->createCourse([
        'name' => 'Admin Assigned Course',
        'start_date' => now()->toDateString(),
        'end_date' => now()->addDays(30)->toDateString(),
        'instructor_id' => $instructor->id,
    ], $admin);

    expect($course->instructor_id)->toBe($instructor->id);
});

it('updates a course', function () {
    $course = Course::factory()->create(['name' => 'Old Name']);

    $result = $this->service->updateCourse($course, ['name' => 'New Name']);

    expect($result)->toBeTrue()
        ->and($course->fresh()->name)->toBe('New Name');
});

it('deletes a course', function () {
    $course = Course::factory()->create();

    $this->service->deleteCourse($course);

    expect(Course::find($course->id))->toBeNull();
});
