<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class UserService
{
    /**
     * Get a paginated list of all users.
     */
    public function listUsers()
    {
        return User::query()->orderBy('name')->paginate(15);
    }

    /**
     * Create a new user.
     */
    public function createUser(array $data): User
    {
        $data['password'] = Hash::make($data['password']);

        return User::create($data);
    }

    /**
     * Update an existing user.
     */
    public function updateUser(User $user, array $data): bool
    {
        if (array_key_exists('password', $data)) {
            if (empty($data['password'])) {
                unset($data['password']);
            } else {
                $data['password'] = Hash::make($data['password']);
            }
        }

        return $user->update($data);
    }

    /**
     * Delete a user.
     */
    public function deleteUser(User $user, User $actingUser): ?bool
    {
        if ($user->id === $actingUser->id) {
            throw new UnprocessableEntityHttpException('You cannot delete your own account.');
        }

        return $user->delete();
    }
}
