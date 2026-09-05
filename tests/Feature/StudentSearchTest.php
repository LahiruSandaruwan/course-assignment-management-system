<?php

use App\Models\User;

it('prevents a student from searching students', function () {
    $student = User::factory()->student()->create();

    $response = $this->actingAs($student)->getJson('/api/students/search?q=test');

    $response->assertStatus(403);
});

it('allows an admin to search students by partial name', function () {
    $admin = User::factory()->admin()->create();
    $match = User::factory()->student()->create(['name' => 'Alice Johnson']);
    User::factory()->student()->create(['name' => 'Bob Smith']);

    $response = $this->actingAs($admin)->getJson('/api/students/search?q=Alice');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $match->id)
        ->assertJsonPath('data.0.name', 'Alice Johnson');
});

it('allows an instructor to search students by email substring', function () {
    $instructor = User::factory()->instructor()->create();
    $match = User::factory()->student()->create(['email' => 'carol.davis@example.com']);
    User::factory()->student()->create(['email' => 'someone.else@example.com']);

    $response = $this->actingAs($instructor)->getJson('/api/students/search?q=carol.davis');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $match->id);
});

it('finds an exact match when searching by numeric id', function () {
    $admin = User::factory()->admin()->create();
    $match = User::factory()->student()->create();
    User::factory()->student()->count(3)->create();

    $response = $this->actingAs($admin)->getJson("/api/students/search?q={$match->id}");

    $response->assertStatus(200)
        ->assertJsonPath('data.0.id', $match->id);
});

it('returns an empty array when nothing matches', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->student()->create(['name' => 'Someone Else']);

    $response = $this->actingAs($admin)->getJson('/api/students/search?q=NoSuchStudent');

    $response->assertStatus(200)
        ->assertJsonCount(0, 'data');
});

it('caps results at 10 even when more students match', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->student()->count(15)->create(['name' => 'Searchable Student']);

    $response = $this->actingAs($admin)->getJson('/api/students/search?q=Searchable');

    $response->assertStatus(200)
        ->assertJsonCount(10, 'data');
});

it('excludes non-student users even when their name matches', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->instructor()->create(['name' => 'Matching Instructor']);
    $studentMatch = User::factory()->student()->create(['name' => 'Matching Student']);

    $response = $this->actingAs($admin)->getJson('/api/students/search?q=Matching');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $studentMatch->id);
});
