<?php

use App\Models\User;
use App\Models\Course;
use App\Enums\Role;
use App\Enums\CourseStatus;
use Illuminate\Support\Facades\DB;

it('allows admin to list students enrolled in a course', function () {
    $admin = User::factory()->admin()->create();
    $student1 = User::factory()->student()->create();
    $student2 = User::factory()->student()->create();
    $course = Course::factory()->create();

    $course->students()->attach([$student1->id, $student2->id]);

    $response = $this->actingAs($admin)->getJson("/api/courses/{$course->id}/students");
    
    $response->assertStatus(200)
        ->assertJsonCount(2, 'data.data')
        ->assertJsonPath('data.data.0.id', $student1->id)
        ->assertJsonPath('data.data.1.id', $student2->id);
});

it('allows admin to enroll a student in a course', function () {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->student()->create();
    $course = Course::factory()->create();

    $response = $this->actingAs($admin)->postJson("/api/courses/{$course->id}/students", [
        'user_id' => $student->id,
    ]);

    $response->assertStatus(201);
    expect($course->students()->where('user_id', $student->id)->exists())->toBeTrue();
});

it('allows instructor to enroll a student in their own course', function () {
    $instructor = User::factory()->instructor()->create();
    $student = User::factory()->student()->create();
    $course = Course::factory()->create([
        'instructor_id' => $instructor->id,
    ]);

    $response = $this->actingAs($instructor)->postJson("/api/courses/{$course->id}/students", [
        'user_id' => $student->id,
    ]);

    $response->assertStatus(201);
});

it('prevents instructor from enrolling a student in someone elses course', function () {
    $instructor1 = User::factory()->instructor()->create();
    $instructor2 = User::factory()->instructor()->create();
    $student = User::factory()->student()->create();
    $course = Course::factory()->create([
        'instructor_id' => $instructor1->id,
    ]);

    $response = $this->actingAs($instructor2)->postJson("/api/courses/{$course->id}/students", [
        'user_id' => $student->id,
    ]);

    $response->assertStatus(403);
});

it('rejects enrollment of a non-student user', function () {
    $admin = User::factory()->admin()->create();
    $instructor = User::factory()->instructor()->create(); // target to enroll
    $course = Course::factory()->create();

    $response = $this->actingAs($admin)->postJson("/api/courses/{$course->id}/students", [
        'user_id' => $instructor->id,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['user_id']);
});

it('prevents duplicate enrollment normally', function () {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->student()->create();
    $course = Course::factory()->create();

    $course->students()->attach($student->id);

    $response = $this->actingAs($admin)->postJson("/api/courses/{$course->id}/students", [
        'user_id' => $student->id,
    ]);

    $response->assertStatus(409)
        ->assertJsonPath('message', 'Student is already enrolled in this course.');
});

it('handles DB-level unique constraint violation securely', function () {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->student()->create();
    $course = Course::factory()->create();

    // Bypass the pre-check by mocking or directly firing the SQL logic inside the service
    $service = new \App\Services\EnrollmentService();
    
    // We can simulate race condition by inserting it under the hood but then letting service do it again? 
    // No, if we insert under the hood, the pre-check will catch it.
    // To truly test the exception catch, we mock the pre-check to return false, but DB throws.
    // Or we can just mock the whole relationship to throw QueryException when attach is called.
    
    $courseMock = Mockery::mock($course)->makePartial();
    $studentsRelMock = Mockery::mock(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class);
    
    $studentsRelMock->shouldReceive('where->exists')->andReturn(false); // Bypass pre-check
    
    // Create a QueryException with SQLite integrity error code (19) or standard 23000
    $exception = new \Illuminate\Database\QueryException(
        'sqlite',
        'insert into course_user',
        [],
        new \PDOException('Integrity constraint violation', 23000)
    );
    
    $studentsRelMock->shouldReceive('attach')->andThrow($exception);
    $courseMock->shouldReceive('students')->andReturn($studentsRelMock);

    expect(function () use ($service, $courseMock, $student, $admin) {
        $service->enrollStudent($courseMock, $student, $admin);
    })->toThrow(\Symfony\Component\HttpKernel\Exception\ConflictHttpException::class, 'Student is already enrolled in this course.');
});

it('allows removal of an enrolled student', function () {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->student()->create();
    $course = Course::factory()->create();

    $course->students()->attach($student->id);

    $response = $this->actingAs($admin)->deleteJson("/api/courses/{$course->id}/students/{$student->id}");

    $response->assertStatus(200);
    expect($course->students()->where('user_id', $student->id)->exists())->toBeFalse();
});
