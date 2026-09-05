<?php

use App\Models\User;

it('allows admin to list users', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->count(3)->student()->create();

    $response = $this->actingAs($admin)->getJson('/api/users');

    $response->assertStatus(200);
    expect(count($response->json('data.data')))->toBeGreaterThanOrEqual(4);
});

it('prevents instructor from listing users', function () {
    $instructor = User::factory()->instructor()->create();

    $this->actingAs($instructor)->getJson('/api/users')->assertStatus(403);
});

it('prevents student from listing users', function () {
    $student = User::factory()->student()->create();

    $this->actingAs($student)->getJson('/api/users')->assertStatus(403);
});

it('allows admin to create a user', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->postJson('/api/users', [
        'name' => 'New Instructor',
        'email' => 'new.instructor@example.com',
        'password' => 'password123',
        'role' => 'instructor',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.name', 'New Instructor')
        ->assertJsonPath('data.email', 'new.instructor@example.com')
        ->assertJsonPath('data.role', 'instructor')
        ->assertJsonMissingPath('data.password');

    $this->assertDatabaseHas('users', ['email' => 'new.instructor@example.com']);

    $created = User::where('email', 'new.instructor@example.com')->first();
    expect($created->password)->not->toBe('password123');
});

it('prevents instructor and student from creating users', function () {
    $instructor = User::factory()->instructor()->create();
    $student = User::factory()->student()->create();

    $payload = [
        'name' => 'Someone',
        'email' => 'someone@example.com',
        'password' => 'password123',
        'role' => 'student',
    ];

    $this->actingAs($instructor)->postJson('/api/users', $payload)->assertStatus(403);
    $this->actingAs($student)->postJson('/api/users', $payload)->assertStatus(403);
});

it('rejects creating a user with a duplicate email', function () {
    $admin = User::factory()->admin()->create();
    $existing = User::factory()->student()->create();

    $response = $this->actingAs($admin)->postJson('/api/users', [
        'name' => 'Duplicate',
        'email' => $existing->email,
        'password' => 'password123',
        'role' => 'student',
    ]);

    $response->assertStatus(422)->assertJsonValidationErrors(['email']);
});

it('rejects creating a user with an invalid role', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->postJson('/api/users', [
        'name' => 'Invalid Role',
        'email' => 'invalid.role@example.com',
        'password' => 'password123',
        'role' => 'superuser',
    ]);

    $response->assertStatus(422)->assertJsonValidationErrors(['role']);
});

it('allows admin to update a user', function () {
    $admin = User::factory()->admin()->create();
    $target = User::factory()->student()->create();

    $response = $this->actingAs($admin)->putJson("/api/users/{$target->id}", [
        'name' => 'Updated Name',
        'role' => 'instructor',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.name', 'Updated Name')
        ->assertJsonPath('data.role', 'instructor');
});

it('does not change the password on update when omitted', function () {
    $admin = User::factory()->admin()->create();
    $target = User::factory()->student()->create();
    $originalHash = $target->password;

    $this->actingAs($admin)->putJson("/api/users/{$target->id}", [
        'name' => 'Same Password',
    ])->assertStatus(200);

    expect($target->fresh()->password)->toBe($originalHash);
});

it('prevents instructor and student from updating users', function () {
    $instructor = User::factory()->instructor()->create();
    $student = User::factory()->student()->create();
    $target = User::factory()->student()->create();

    $this->actingAs($instructor)->putJson("/api/users/{$target->id}", ['name' => 'X'])->assertStatus(403);
    $this->actingAs($student)->putJson("/api/users/{$target->id}", ['name' => 'X'])->assertStatus(403);
});

it('allows admin to delete another user', function () {
    $admin = User::factory()->admin()->create();
    $target = User::factory()->student()->create();

    $this->actingAs($admin)->deleteJson("/api/users/{$target->id}")->assertStatus(200);

    $this->assertDatabaseMissing('users', ['id' => $target->id]);
});

it('prevents admin from deleting their own account', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->deleteJson("/api/users/{$admin->id}");

    $response->assertStatus(422);
    $this->assertDatabaseHas('users', ['id' => $admin->id]);
});

it('prevents instructor and student from deleting users', function () {
    $instructor = User::factory()->instructor()->create();
    $student = User::factory()->student()->create();
    $target = User::factory()->student()->create();

    $this->actingAs($instructor)->deleteJson("/api/users/{$target->id}")->assertStatus(403);
    $this->actingAs($student)->deleteJson("/api/users/{$target->id}")->assertStatus(403);
});

it('prevents deleting an instructor who is assigned to a course', function () {
    $admin = User::factory()->admin()->create();
    $instructor = User::factory()->instructor()->create();
    \App\Models\Course::factory()->create(['instructor_id' => $instructor->id]);

    $response = $this->actingAs($admin)->deleteJson("/api/users/{$instructor->id}");

    $response->assertStatus(422)->assertJsonValidationErrors(['user']);
    $this->assertDatabaseHas('users', ['id' => $instructor->id]);
});

it('prevents deleting a student who has a submission', function () {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->student()->create();
    \App\Models\Submission::factory()->create(['student_id' => $student->id]);

    $response = $this->actingAs($admin)->deleteJson("/api/users/{$student->id}");

    $response->assertStatus(422)->assertJsonValidationErrors(['user']);
    $this->assertDatabaseHas('users', ['id' => $student->id]);
});

it('blocks deleting an instructor with a course at the database level, independent of the app guard', function () {
    $instructor = User::factory()->instructor()->create();
    \App\Models\Course::factory()->create(['instructor_id' => $instructor->id]);

    expect(fn () => \Illuminate\Support\Facades\DB::table('users')->where('id', $instructor->id)->delete())
        ->toThrow(\Illuminate\Database\QueryException::class);

    $this->assertDatabaseHas('users', ['id' => $instructor->id]);
});
