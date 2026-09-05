<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

it('logs a warning on a failed login attempt', function () {
    Log::spy();

    User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password123'),
    ]);

    $this->postJson('/api/login', [
        'email' => 'test@example.com',
        'password' => 'wrongpassword',
    ])->assertStatus(401);

    Log::shouldHaveReceived('warning')
        ->once()
        ->withArgs(fn ($message, $context) => $message === 'Failed login attempt'
            && $context['email'] === 'test@example.com');
});

it('logs a warning when an action is denied by a policy', function () {
    Log::spy();

    $student = User::factory()->student()->create();

    $this->actingAs($student)->postJson('/api/users', [
        'name' => 'New User',
        'email' => 'new@example.com',
        'password' => 'password123',
        'role' => 'student',
    ])->assertStatus(403);

    Log::shouldHaveReceived('warning')
        ->once()
        ->withArgs(fn ($message, $context) => $message === 'Authorization denied'
            && $context['user_id'] === $student->id);
});
