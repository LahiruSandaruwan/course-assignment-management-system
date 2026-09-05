<?php

use App\Models\User;
use App\Models\Course;
use App\Enums\Role;
use App\Enums\CourseStatus;

it('allows admin to create a course', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->postJson('/api/courses', [
        'name' => 'Test Course',
        'description' => 'Test Description',
        'start_date' => '2025-01-01',
        'end_date' => '2025-12-31',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.name', 'Test Course')
        ->assertJsonPath('data.instructor_id', $admin->id); // defaulted to self
});

it('allows instructor to create a course', function () {
    $instructor = User::factory()->instructor()->create();

    $response = $this->actingAs($instructor)->postJson('/api/courses', [
        'name' => 'Instructor Course',
        'start_date' => '2025-01-01',
        'end_date' => '2025-12-31',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.name', 'Instructor Course')
        ->assertJsonPath('data.instructor_id', $instructor->id);
});

it('prevents student from creating a course', function () {
    $student = User::factory()->student()->create();

    $response = $this->actingAs($student)->postJson('/api/courses', [
        'name' => 'Student Course',
        'start_date' => '2025-01-01',
        'end_date' => '2025-12-31',
    ]);

    $response->assertStatus(403);
});

it('allows admin to view all courses', function () {
    $admin = User::factory()->admin()->create();
    $instructor = User::factory()->instructor()->create();
    
    Course::create([
        'name' => 'Course 1',
        'instructor_id' => $instructor->id,
        'start_date' => '2025-01-01',
        'end_date' => '2025-12-31',
        'status' => CourseStatus::Active->value,
    ]);

    Course::create([
        'name' => 'Course 2',
        'instructor_id' => $instructor->id,
        'start_date' => '2025-01-01',
        'end_date' => '2025-12-31',
        'status' => CourseStatus::Archived->value,
    ]);

    $response = $this->actingAs($admin)->getJson('/api/courses');
    
    $response->assertStatus(200)
        ->assertJsonCount(2, 'data.data');
});

it('restricts instructor to view only their own courses', function () {
    $instructor1 = User::factory()->instructor()->create();
    $instructor2 = User::factory()->instructor()->create();
    
    Course::create([
        'name' => 'Course 1',
        'instructor_id' => $instructor1->id,
        'start_date' => '2025-01-01',
        'end_date' => '2025-12-31',
        'status' => CourseStatus::Active->value,
    ]);

    Course::create([
        'name' => 'Course 2',
        'instructor_id' => $instructor2->id,
        'start_date' => '2025-01-01',
        'end_date' => '2025-12-31',
        'status' => CourseStatus::Active->value,
    ]);

    $response = $this->actingAs($instructor1)->getJson('/api/courses');
    
    $response->assertStatus(200)
        ->assertJsonCount(1, 'data.data')
        ->assertJsonPath('data.data.0.name', 'Course 1');
});

it('restricts student to view only active courses', function () {
    $student = User::factory()->student()->create();
    $instructor = User::factory()->instructor()->create();
    
    Course::create([
        'name' => 'Active Course',
        'instructor_id' => $instructor->id,
        'start_date' => '2025-01-01',
        'end_date' => '2025-12-31',
        'status' => CourseStatus::Active->value,
    ]);

    Course::create([
        'name' => 'Archived Course',
        'instructor_id' => $instructor->id,
        'start_date' => '2025-01-01',
        'end_date' => '2025-12-31',
        'status' => CourseStatus::Archived->value,
    ]);

    $response = $this->actingAs($student)->getJson('/api/courses');
    
    $response->assertStatus(200)
        ->assertJsonCount(1, 'data.data')
        ->assertJsonPath('data.data.0.name', 'Active Course');
});

it('allows instructor to update their own course', function () {
    $instructor = User::factory()->instructor()->create();
    $course = Course::create([
        'name' => 'Old Name',
        'instructor_id' => $instructor->id,
        'start_date' => '2025-01-01',
        'end_date' => '2025-12-31',
    ]);

    $response = $this->actingAs($instructor)->putJson("/api/courses/{$course->id}", [
        'name' => 'New Name'
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.name', 'New Name');
});

it('prevents instructor from updating someone elses course', function () {
    $instructor1 = User::factory()->instructor()->create();
    $instructor2 = User::factory()->instructor()->create();
    
    $course = Course::create([
        'name' => 'Instructor 1 Course',
        'instructor_id' => $instructor1->id,
        'start_date' => '2025-01-01',
        'end_date' => '2025-12-31',
    ]);

    $response = $this->actingAs($instructor2)->putJson("/api/courses/{$course->id}", [
        'name' => 'Hacked Name'
    ]);

    $response->assertStatus(403);
});
