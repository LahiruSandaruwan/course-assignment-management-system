<?php

namespace App\Policies;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AssignmentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user, Course $course): bool
    {
        if ($user->role === \App\Enums\Role::Admin) {
            return true;
        }

        if ($user->role === \App\Enums\Role::Instructor) {
            return $user->id === $course->instructor_id;
        }

        // Students can view assignments for courses they are enrolled in
        return $course->students()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Assignment $assignment): bool
    {
        $course = $assignment->course;

        if ($user->role === \App\Enums\Role::Admin) {
            return true;
        }

        if ($user->role === \App\Enums\Role::Instructor) {
            return $user->id === $course->instructor_id;
        }

        // Student logic: must be enrolled AND assignment must be published
        if ($assignment->status !== \App\Enums\AssignmentStatus::Published) {
            return false;
        }

        return $course->students()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, Course $course): bool
    {
        if ($user->role === \App\Enums\Role::Admin) {
            return true;
        }

        if ($user->role === \App\Enums\Role::Instructor) {
            return $user->id === $course->instructor_id;
        }

        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Assignment $assignment): bool
    {
        if ($user->role === \App\Enums\Role::Admin) {
            return true;
        }

        if ($user->role === \App\Enums\Role::Instructor) {
            return $user->id === $assignment->course->instructor_id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Assignment $assignment): bool
    {
        return $this->update($user, $assignment);
    }
}
