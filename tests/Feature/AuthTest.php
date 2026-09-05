<?php

use App\Models\User;
use App\Enums\Role;
use Illuminate\Support\Facades\Hash;

it('allows a user to login with valid credentials', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password123'),
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'user' => ['id', 'name', 'email', 'role'],
                'token'
            ],
            'message'
        ]);
});

it('rejects login with invalid credentials', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password123'),
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'test@example.com',
        'password' => 'wrongpassword',
    ]);

    $response->assertStatus(401);
});

it('validates required fields on login', function () {
    $response = $this->postJson('/api/login', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email', 'password']);
});

it('allows an authenticated user to get their profile', function () {
    $user = User::factory()->admin()->create();

    $response = $this->actingAs($user)->getJson('/api/me');

    $response->assertStatus(200)
        ->assertJsonPath('data.user.id', $user->id)
        ->assertJsonPath('data.user.role', Role::Admin->value);
});

it('allows an authenticated user to logout', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->postJson('/api/logout');

    $response->assertStatus(200);

    expect($user->tokens()->count())->toBe(0);
});

it('throttles repeated login attempts', function () {
    $user = User::factory()->create([
        'email' => 'throttle@example.com',
        'password' => Hash::make('password123'),
    ]);

    for ($i = 0; $i < 5; $i++) {
        $this->postJson('/api/login', [
            'email' => 'throttle@example.com',
            'password' => 'wrongpassword',
        ])->assertStatus(401);
    }

    $response = $this->postJson('/api/login', [
        'email' => 'throttle@example.com',
        'password' => 'wrongpassword',
    ]);

    $response->assertStatus(429);
});
